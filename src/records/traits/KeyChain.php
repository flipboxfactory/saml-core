<?php


namespace flipbox\saml\core\records\traits;


use flipbox\keychain\records\KeyChainRecord;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Trait KeyPair
 * @package flipbox\saml\core\records\traits
 * @property KeyChainRecord $keychain
 */
trait KeyChain
{
    /**
     * @return string
     */
    public function defaultCipherType()
    {
        return XMLSecurityKey::RSA_SHA256;
    }

    /**
     * @return XMLSecurityKey
     * @throws \Exception
     */
    public function keychainPrivateXmlSecurityKey()
    {
        $xmlSecurityKey = new XMLSecurityKey($this->defaultCipherType(), [
            'type' => 'private',
        ]);

        $xmlSecurityKey->loadKey($this->keychain->getDecryptedKey());

        return $xmlSecurityKey;
    }

    /**
     * @return XMLSecurityKey
     * @throws \Exception
     */
    public function keychainPublicXmlSecurityKey()
    {
        $xmlSecurityKey = new XMLSecurityKey($this->defaultCipherType(), [
            'type' => 'public',
        ]);

        $xmlSecurityKey->loadKey($this->keychain->getDecryptedCertificate());

        return $xmlSecurityKey;

    }

}