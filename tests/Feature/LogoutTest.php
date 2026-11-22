<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\tests\fixtures\KeyPair;
use flipbox\saml\core\tests\fixtures\plugin\ProviderIdentityRecord;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Constants;
use SAML2\HTTPRedirect;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;

const IDP = 'https://idp.example.com';

beforeEach(function () {
    $this->idpKeys = KeyPair::generate();

    // This system (the SP), with a key pair to sign its LogoutResponses
    $own = $this->plugin()->getProvider()->create(
        new SAML2\XML\md\EntityDescriptor(SAML2\DOMDocumentFactory::fromString(idpMetadataXml($this->plugin()->getSettings()->getEntityId(), [keyPair()->certificatePem]))->documentElement),
    );
    $own->providerType = 'sp';
    $own->setKeychain(KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']));
    $this->own = $this->plugin()->getProvider()->save($own);

    $idp = new ProviderRecord(['providerType' => 'idp', 'label' => 'IdP']);
    $idp->metadata = idpMetadataXml(IDP, [$this->idpKeys->certificatePem]);
    $this->idp = $this->plugin()->getProvider()->save($idp);

    $this->user = $this->admin();
    $this->identity = ProviderIdentityRecord::query()->create([
        'providerId' => $this->idp->id, 'userId' => $this->user->id, 'nameId' => 'jane@example.com', 'lastLoginDate' => now(),
    ]);
});

function signingKey(KeyPair $keyPair): XMLSecurityKey
{
    $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    $key->loadKey($keyPair->privateKeyPem);

    return $key;
}

function idpLogoutRequest(?KeyPair $signer, string $issuer = IDP, string $nameId = 'jane@example.com'): LogoutRequest
{
    $request = new LogoutRequest;
    $request->setIssuer(tap(new Issuer, fn ($i) => $i->setValue($issuer)));
    $request->setNameID(tap(new NameID, fn ($n) => $n->setValue($nameId)));
    $request->setDestination('http://localhost/sso/logout');
    if ($signer) {
        $request->setSignatureKey(signingKey($signer));
    }

    return $request;
}

function postMessage(LogoutRequest|LogoutResponse $message): array
{
    $xml = $message->toSignedXML();
    $key = $message instanceof LogoutRequest ? 'SAMLRequest' : 'SAMLResponse';

    return [$key => base64_encode($xml->ownerDocument->saveXML($xml))];
}

it('rejects an unsigned LogoutRequest and keeps the user logged in', function () {
    $this->actingAs($this->user, 'craft')
        ->post('/sso/logout', postMessage(idpLogoutRequest(null)))
        ->assertStatus(400);

    $this->assertAuthenticated('craft');
});

it('rejects a LogoutRequest signed with a key that isn’t in the IdP’s metadata', function () {
    $this->post('/sso/logout', postMessage(idpLogoutRequest(KeyPair::generate())))->assertStatus(400);
});

it('rejects a LogoutRequest from an unknown or disabled provider', function () {
    $this->post('/sso/logout', postMessage(idpLogoutRequest($this->idpKeys, 'https://unknown.example.com')))->assertStatus(400);

    $this->idp->update(['enabled' => false]);
    $this->post('/sso/logout', postMessage(idpLogoutRequest($this->idpKeys)))->assertStatus(400);
});

it('rejects a signed LogoutRequest meant for another endpoint', function () {
    $request = idpLogoutRequest($this->idpKeys);
    $request->setDestination('https://elsewhere.example.com/sso/logout');

    $this->actingAs($this->user, 'craft')->post('/sso/logout', postMessage($request))->assertStatus(400);

    $this->assertAuthenticated('craft');
});

it('rejects a message that isn’t a logout message', function () {
    $this->post('/sso/logout', ['SAMLRequest' => base64_encode('<not-saml/>')])->assertStatus(400);
});

it('logs out and answers a signed LogoutRequest with a signed LogoutResponse', function () {
    $request = idpLogoutRequest($this->idpKeys);

    $response = $this->actingAs($this->user, 'craft')->post('/sso/logout', postMessage($request))->assertOk();

    $this->assertGuest('craft');
    preg_match('/name="SAMLResponse" value="([^"]+)"/', $response->getContent(), $m);
    $logoutResponse = SAML2\Message::fromXML(SAML2\DOMDocumentFactory::fromString(base64_decode(html_entity_decode($m[1])))->documentElement);

    expect($response->getContent())->toContain('action="'.IDP.'/slo"')
        ->and($logoutResponse)->toBeInstanceOf(LogoutResponse::class)
        ->and($logoutResponse->getInResponseTo())->toBe($request->getId())
        ->and($logoutResponse->validate($this->own->keychain ? (function () {
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
            $key->loadKey($this->own->keychain->getDecryptedCertificate(), false, true);

            return $key;
        })->call($this) : null))->toBeTrue();
});

it('accepts a LogoutRequest signed with the HTTP-Redirect binding', function () {
    $url = (new HTTPRedirect)->getRedirectURL(idpLogoutRequest($this->idpKeys));

    $this->actingAs($this->user, 'craft')->get('/sso/logout?'.parse_url($url, PHP_URL_QUERY))->assertOk();

    $this->assertGuest('craft');
});

it('only ends the named user’s sessions for a signed LogoutRequest', function () {
    $this->plugin()->getSettings()->sloDestroySpecifiedSessions = true;
    $rememberToken = fn () => User::query()->whereKey($this->user->id)->value('rememberToken');
    $before = $rememberToken();

    $this->post('/sso/logout', postMessage(idpLogoutRequest(null)))->assertStatus(400);
    expect($rememberToken())->toBe($before);

    $this->post('/sso/logout', postMessage(idpLogoutRequest($this->idpKeys)))->assertOk();
    expect($rememberToken())->not->toBe($before);
});

it('only accepts a LogoutResponse to this session’s request', function () {
    $logoutResponse = fn (string $inResponseTo) => tap(new LogoutResponse, function (LogoutResponse $r) use ($inResponseTo) {
        $r->setIssuer(tap(new Issuer, fn ($i) => $i->setValue(IDP)));
        $r->setInResponseTo($inResponseTo);
        $r->setDestination('http://localhost/sso/logout');
        $r->setSignatureKey(signingKey($this->idpKeys));
    });

    // Start a logout, which remembers the LogoutRequest's ID in the session
    $started = $this->actingAs($this->user, 'craft')->get('/sso/logout/request/'.$this->idp->uid)->assertOk();
    preg_match('/name="SAMLRequest" value="([^"]+)"/', $started->getContent(), $m);
    $requestId = SAML2\Message::fromXML(SAML2\DOMDocumentFactory::fromString(base64_decode(html_entity_decode($m[1])))->documentElement)->getId();
    $cookie = $started->getCookie(config('session.cookie'), false);

    $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
        ->post('/sso/logout', postMessage($logoutResponse('_unexpected')))
        ->assertStatus(400);

    $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
        ->post('/sso/logout', postMessage($logoutResponse($requestId)))
        ->assertRedirect();
});

it('starts a logout with a signed LogoutRequest for the user’s identity', function () {
    $response = $this->actingAs($this->user, 'craft')->get('/sso/logout/request/'.$this->idp->uid)->assertOk();

    preg_match('/name="SAMLRequest" value="([^"]+)"/', $response->getContent(), $m);
    $request = SAML2\Message::fromXML(SAML2\DOMDocumentFactory::fromString(base64_decode(html_entity_decode($m[1])))->documentElement);

    expect($request)->toBeInstanceOf(LogoutRequest::class)
        ->and($request->getNameId()->getValue())->toBe('jane@example.com')
        ->and($request->getCertificates()[0] ?? '')->not->toContain('eyJ')
        ->and(session('saml.core/saml-core-test/request.id'))->toBe($request->getId());
});
