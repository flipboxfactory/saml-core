<?php

namespace flipbox\saml\core\tests\unit\helpers;

use Codeception\Test\Unit;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\containers\Saml2Container;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\tests\fixtures\KeyPair;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Compat\ContainerSingleton;
use SAML2\EncryptedAssertion;
use SAML2\XML\saml\Issuer;

class SecurityHelperTest extends Unit
{
    private static ?KeyPair $keyPair = null;

    protected function _before(): void
    {
        self::$keyPair ??= KeyPair::generate();
        ContainerSingleton::setContainer(new Saml2Container($this->createMock(AbstractPlugin::class)));
    }

    public function testCleanCertificateStripsArmorAndWhitespace(): void
    {
        $clean = SecurityHelper::cleanCertificate(self::$keyPair->certificatePem);

        $this->assertStringNotContainsString('-----', $clean);
        $this->assertDoesNotMatchRegularExpression('/\s/', $clean);
        $this->assertNotFalse(base64_decode($clean, true));
    }

    public function testCleanCertificateRejectsNonPem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SecurityHelper::cleanCertificate('not a certificate');
    }

    public function testConvertToCertificateProducesEquivalentPem(): void
    {
        $pem = SecurityHelper::convertToCertificate(self::$keyPair->certificatePem);

        $this->assertSame(
            openssl_x509_fingerprint(self::$keyPair->certificatePem, 'sha256'),
            openssl_x509_fingerprint($pem, 'sha256')
        );
    }

    /**
     * @dataProvider digestProvider
     */
    public function testGetPemAlgorithm(string $digest, string $expected): void
    {
        $this->assertSame($expected, SecurityHelper::getPemAlgorithm(KeyPair::generate($digest)->certificatePem));
    }

    public function digestProvider(): array
    {
        return [
            'sha256' => ['sha256', XMLSecurityKey::RSA_SHA256],
            'sha384' => ['sha384', XMLSecurityKey::RSA_SHA384],
            'sha512' => ['sha512', XMLSecurityKey::RSA_SHA512],
        ];
    }

    public function testDecryptAssertionRoundTrip(): void
    {
        $assertion = $this->createAssertion();
        $encrypted = $this->encrypt($assertion);

        $decrypted = SecurityHelper::decryptAssertion($encrypted, self::$keyPair->privateKeyPem);

        $this->assertSame($assertion->getId(), $decrypted->getId());
        $this->assertSame('https://idp.example.com', $decrypted->getIssuer()->getValue());
    }

    public function testDecryptAssertionHonoursBlacklist(): void
    {
        $encrypted = $this->encrypt($this->createAssertion());

        $this->expectException(\Exception::class);
        SecurityHelper::decryptAssertion(
            $encrypted,
            self::$keyPair->privateKeyPem,
            [XMLSecurityKey::RSA_OAEP_MGF1P]
        );
    }

    public function testDecryptAssertionFailsWithWrongKey(): void
    {
        $encrypted = $this->encrypt($this->createAssertion());

        $this->expectException(\Exception::class);
        SecurityHelper::decryptAssertion($encrypted, KeyPair::generate()->privateKeyPem);
    }

    private function createAssertion(): Assertion
    {
        $issuer = new Issuer();
        $issuer->setValue('https://idp.example.com');
        $assertion = new Assertion();
        $assertion->setIssuer($issuer);

        return $assertion;
    }

    /**
     * Encrypts with the certificate's public key, then round-trips through XML as a real IdP response would.
     */
    private function encrypt(Assertion $assertion): EncryptedAssertion
    {
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
        $key->loadKey(self::$keyPair->certificatePem, false, true);

        $encrypted = new EncryptedAssertion();
        $encrypted->setAssertion($assertion, $key);

        return new EncryptedAssertion($encrypted->toXML());
    }
}
