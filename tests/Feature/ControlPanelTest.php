<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;

beforeEach(fn () => $this->actingAs($this->admin(), 'craft'));

function savedIdp(): ProviderRecord
{
    $provider = new ProviderRecord(['providerType' => 'idp', 'label' => 'Okta']);
    $provider->metadata = idpMetadataXml('https://idp.example.com', [keyPair()->certificatePem]);

    return test()->plugin()->getProvider()->save($provider);
}

it('renders every screen', function (string $uri) {
    $this->get($uri)->assertOk();
})->with([
    '/admin/saml-core-test',
    '/admin/saml-core-test/settings',
    '/admin/saml-core-test/metadata',
    '/admin/saml-core-test/metadata/new',
    '/admin/saml-core-test/metadata/new-idp',
    '/admin/saml-core-test/metadata/new-sp',
    '/admin/saml-core-test/metadata/my-provider',
    '/admin/saml-core-test/keychain',
    '/admin/saml-core-test/keychain/new',
    '/admin/saml-core-test/keychain/new-openssl',
]);

it('renders a provider and its login/logout paths', function () {
    $idp = savedIdp();

    $this->get("/admin/saml-core-test/metadata/$idp->id")
        ->assertOk()
        ->assertSee('Okta')
        ->assertSee(str_replace('/', '\/', $idp->getLoginRequestPath()), false);
});

it('404s for a missing provider', function () {
    $this->get('/admin/saml-core-test/metadata/999')->assertNotFound();
});

it('saves an IdP from pasted metadata', function () {
    $this->post('/admin/actions/saml-core-test/metadata/save', [
        'providerType' => 'idp',
        'label' => 'Okta',
        'metadata-text' => idpMetadataXml('https://idp.example.com', [keyPair()->certificatePem]),
        'mapping' => [['attributeName' => 'mail', 'craftProperty' => 'email']],
        'syncGroups' => '1',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $idp = ProviderRecord::query()->where('entityId', 'https://idp.example.com')->sole();
    expect($idp->label)->toBe('Okta')
        ->and($idp->getMapping())->toBe([['attributeName' => 'mail', 'craftProperty' => 'email']]);
});

it('rejects invalid or missing metadata and a missing label', function () {
    $this->from('/admin/saml-core-test/metadata/new-idp')
        ->post('/admin/actions/saml-core-test/metadata/save', ['providerType' => 'idp', 'metadata-text' => '<oops'])
        ->assertRedirect('/admin/saml-core-test/metadata/new-idp')
        ->assertSessionHasErrors(['label', 'metadata-text']);

    $this->post('/admin/actions/saml-core-test/metadata/save', ['providerType' => 'idp', 'label' => 'Empty'])
        ->assertSessionHasErrors(['metadata-text']);

    expect(ProviderRecord::query()->count())->toBe(0);
});

it('creates my provider from the settings and a key pair', function () {
    $keyPair = KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']);

    $this->post('/admin/actions/saml-core-test/metadata/auto-create', [
        'label' => 'This site',
        'keychain' => $keyPair->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $own = $this->plugin()->getProvider()->findOwn();
    expect($own)->not->toBeNull()
        ->and($own->providerType)->toBe('sp')
        ->and($own->keychain->id)->toBe($keyPair->id)
        ->and($own->toXmlString())->toContain('/sso/login/'.$own->uid)
        ->and($own->toXmlString())->toContain(SecurityHelper::cleanCertificate($keyPair->getDecryptedCertificate()));

    $this->get('/admin/actions/saml-core-test/metadata')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
        ->assertSee($own->entityId, false);
});

it('stores group options from the access control table', function () {
    // The IdP plugin side edits SPs' group access
    $this->plugin()->getSettings()->setMyType('idp');

    $this->post('/admin/actions/saml-core-test/metadata/save', [
        'providerType' => 'sp',
        'label' => 'App',
        'metadata-text' => idpMetadataXml('https://sp.example.com', [keyPair()->certificatePem]),
        'groupOptions' => [
            GroupOptions::ALLOW_ANY => ['allow' => '0', 'sync' => '0'],
            GroupOptions::NO_GROUP => ['allow' => '1', 'sync' => '0'],
            '7' => ['allow' => '1', 'sync' => '1'],
            'bogus' => ['allow' => '1'],
        ],
    ])->assertSessionHasNoErrors();

    $options = ProviderRecord::query()->where('entityId', 'https://sp.example.com')->sole()->getGroupOptions();
    expect($options->getAllow())->toBe([GroupOptions::NO_GROUP, 7])
        ->and($options->getSync())->toBe([7]);
});

it('toggles and deletes a provider', function () {
    $idp = savedIdp();

    $this->post('/admin/actions/saml-core-test/metadata/change-status', ['identifier' => $idp->id])->assertRedirect();
    expect(ProviderRecord::find($idp->id)->enabled)->toBeFalse();

    $this->deleteJson('/admin/actions/saml-core-test/metadata/delete', ['id' => $idp->id])->assertOk();
    expect(ProviderRecord::find($idp->id))->toBeNull();
});

it('downloads a key pair’s certificate but never its key', function () {
    $keyPair = KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']);

    $response = $this->get('/admin/actions/saml-core-test/metadata/download-certificate?keyId='.$keyPair->id)->assertOk();

    expect($response->getContent())->toContain('BEGIN CERTIFICATE')->not->toContain('PRIVATE KEY');
});

it('saves settings without losing others', function () {
    $this->plugin()->getSettings()->sloDestroySpecifiedSessions = true;

    $this->post('/admin/actions/saml-core-test/settings/save', [
        'entityId' => 'https://sp.example.com',
        'endpointPrefix' => 'saml',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $settings = $this->plugin()->getSettings();
    expect($settings->entityId)->toBe('https://sp.example.com')
        ->and($settings->endpointPrefix)->toBe('saml')
        ->and($settings->sloDestroySpecifiedSessions)->toBeTrue();
});

it('rejects an endpoint prefix with unsafe characters', function () {
    $this->post('/admin/actions/saml-core-test/settings/save', ['entityId' => 'x', 'endpointPrefix' => 'sso?x=<y>'])
        ->assertSessionHasErrors(['endpointPrefix']);
});
