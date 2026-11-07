<?php

namespace flipbox\saml\core\tests\unit\helpers;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\containers\Saml2Container;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\tests\fixtures\KeyPair;
use PHPUnit\Framework\TestCase;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Compat\ContainerSingleton;
use SAML2\EncryptedAssertion;
use SAML2\XML\saml\Issuer;

class SecurityHelperTest extends TestCase
{
    /** @var KeyPair|null */
    private static $keyPair;

    protected function setUp(): void
    {
        self::$keyPair = self::$keyPair ?? KeyPair::generate();
        ContainerSingleton::setContainer(new Saml2Container($this->createMock(AbstractPlugin::class)));
    }

    public function testDecryptAssertionRoundTrip(): void
    {
        $assertion = $this->createAssertion();

        $decrypted = SecurityHelper::decryptAssertion($this->encrypt($assertion), self::$keyPair->privateKeyPem);

        $this->assertSame($assertion->getId(), $decrypted->getId());
        $this->assertSame('https://idp.example.com', $decrypted->getIssuer()->getValue());
    }

    /**
     * A blacklisted key transport algorithm must be rejected; saml2 only enforces it if the blacklist is passed through.
     */
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
