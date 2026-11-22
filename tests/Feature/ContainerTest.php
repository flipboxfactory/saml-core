<?php

declare(strict_types=1);

use flipbox\saml\core\containers\Saml2Container;
use flipbox\saml\core\services\bindings\Factory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use SAML2\AuthnRequest;
use SAML2\Compat\ContainerSingleton;
use SAML2\XML\saml\Issuer;

function authnRequestXml(): string
{
    $issuer = new Issuer;
    $issuer->setValue('https://sp.example.com');
    $request = new AuthnRequest;
    $request->setIssuer($issuer);
    $request->setDestination('https://idp.example.com/sso');
    $xml = $request->toUnsignedXML();

    return $xml->ownerDocument->saveXML($xml);
}

it('limits inflated messages to 8 MB', function () {
    expect(ContainerSingleton::getInstance()->getInflateSizeLimit())->toBe(8 * 1024 * 1024);
});

it('receives an HTTP-Redirect message from the request', function () {
    $request = Request::create('/sso/login?SAMLRequest='.urlencode(base64_encode(gzdeflate(authnRequestXml()))));

    $message = Factory::receive($request);

    expect($message)->toBeInstanceOf(AuthnRequest::class)
        ->and($message->getIssuer()->getValue())->toBe('https://sp.example.com');
});

it('receives an HTTP-POST message from the request', function () {
    $request = Request::create('/sso/login', 'POST', ['SAMLRequest' => base64_encode(authnRequestXml())]);

    expect(Factory::receive($request))->toBeInstanceOf(AuthnRequest::class);
});

it('restores the superglobals after receiving', function () {
    $_POST = ['untouched' => '1'];
    Factory::receive(Request::create('/sso/login', 'POST', ['SAMLRequest' => base64_encode(authnRequestXml())]));

    expect($_POST)->toBe(['untouched' => '1']);
});

it('rejects a DEFLATE bomb', function () {
    $bomb = gzdeflate(str_repeat('A', Saml2Container::INFLATE_SIZE_LIMIT * 4), 9);
    expect(strlen($bomb))->toBeLessThan(64 * 1024);

    try {
        Factory::receive(Request::create('/sso/login?SAMLRequest='.urlencode(base64_encode($bomb))));
    } catch (Throwable $e) {
        expect($e->getMessage())->toMatch('/inflat|insufficient memory/i');

        return;
    }

    $this->fail('An oversized DEFLATE payload was inflated.');
});

it('redirects by throwing a response', function () {
    try {
        ContainerSingleton::getInstance()->redirect('https://idp.example.com/sso?a=1', ['SAMLRequest' => 'x y']);
    } catch (HttpResponseException $e) {
        expect($e->getResponse()->getStatusCode())->toBe(302)
            ->and($e->getResponse()->headers->get('Location'))->toBe('https://idp.example.com/sso?a=1&SAMLRequest=x+y');

        return;
    }

    $this->fail('No response was thrown.');
});

it('posts with an uncached auto-submitting form', function () {
    try {
        ContainerSingleton::getInstance()->postRedirect('https://idp.example.com/sso', ['SAMLRequest' => 'PHNhbWw+', 'RelayState' => '<script>']);
    } catch (HttpResponseException $e) {
        $html = (string) $e->getResponse()->getContent();

        expect($e->getResponse()->headers->get('Cache-Control'))->toContain('no-store')
            ->and($html)->toContain('action="https://idp.example.com/sso"')
            ->and($html)->toContain('value="PHNhbWw+"')
            ->and($html)->not->toContain('<script>');

        return;
    }

    $this->fail('No response was thrown.');
});

it('keeps temporary files in storage', function () {
    expect(ContainerSingleton::getInstance()->getTempDir())->toBe(storage_path('saml2'))->toBeDirectory();
});
