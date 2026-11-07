<?php

namespace flipbox\saml\core\tests\unit\containers;

use Codeception\Test\Unit;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\containers\Saml2Container;
use SAML2\AuthnRequest;
use SAML2\Compat\ContainerSingleton;
use SAML2\HTTPRedirect;
use SAML2\XML\saml\Issuer;

class Saml2ContainerTest extends Unit
{
    private Saml2Container $container;

    private array $server;

    protected function _before(): void
    {
        $this->server = $_SERVER;
        $this->container = new Saml2Container($this->createMock(AbstractPlugin::class));
        ContainerSingleton::setContainer($this->container);
    }

    protected function _after(): void
    {
        $_SERVER = $this->server;
    }

    public function testInflateSizeLimitIsEightMegabytes(): void
    {
        $this->assertSame(8 * 1024 * 1024, $this->container->getInflateSizeLimit());
    }

    public function testRedirectBindingReceivesMessage(): void
    {
        $issuer = new Issuer();
        $issuer->setValue('https://sp.example.com');
        $request = new AuthnRequest();
        $request->setIssuer($issuer);
        $request->setDestination('https://idp.example.com/sso');

        $xml = $request->toUnsignedXML();
        $this->setRedirectQuery(gzdeflate($xml->ownerDocument->saveXML($xml)));

        $received = (new HTTPRedirect())->receive();

        $this->assertInstanceOf(AuthnRequest::class, $received);
        $this->assertSame('https://sp.example.com', $received->getIssuer()->getValue());
    }

    /**
     * GHSA-6vhm-xfmv-94pj: a few KB of DEFLATE data must not inflate past the container's limit.
     * gzinflate()'s limit is enforced per zlib chunk, so the payload is well over it.
     * Without the fix this fails later, with an XML parse error, after inflating everything.
     */
    public function testRedirectBindingRejectsDecompressionBomb(): void
    {
        $bomb = gzdeflate(str_repeat('A', $this->container->getInflateSizeLimit() * 4), 9);
        $this->assertLessThan(64 * 1024, strlen($bomb));
        $this->setRedirectQuery($bomb);

        try {
            (new HTTPRedirect())->receive();
            $this->fail('An oversized DEFLATE payload was inflated.');
        } catch (\Throwable $e) {
            $this->assertMatchesRegularExpression('/inflat|insufficient memory/i', $e->getMessage());
        }
    }

    public function testTempDirIsCreatedInCraftStorage(): void
    {
        $tempDir = $this->container->getTempDir();

        $this->assertDirectoryExists($tempDir);
        $this->assertSame(\Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . 'saml2', $tempDir);
    }

    public function testGenerateId(): void
    {
        $this->assertMatchesRegularExpression('/^_[0-9a-f]{42}$/', $this->container->generateId());
    }

    private function setRedirectQuery(string $deflated): void
    {
        $_SERVER['QUERY_STRING'] = 'SAMLRequest=' . urlencode(base64_encode($deflated));
    }
}
