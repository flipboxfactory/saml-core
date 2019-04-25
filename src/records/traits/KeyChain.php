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
    public function getCipherType()
    {
        return XMLSecurityKey::RSA_SHA256;
    }

    /**
     * @return XMLSecurityKey
     * @throws \Exception
     */
    public function getPrivateXmlSecurityKey()
    {
        $xmlSecurityKey = new XMLSecurityKey($this->getCipherType(), [
            'type' => 'private',
        ]);

        $xmlSecurityKey->loadKey($this->keychain->getDecryptedKey());

        return $xmlSecurityKey;
    }

    /**
     * @return XMLSecurityKey
     * @throws \Exception
     */
    public function getPublicXmlSecurityKey()
    {
        $xmlSecurityKey = new XMLSecurityKey($this->getCipherType(), [
            'type' => 'public',
        ]);

        $xmlSecurityKey->loadKey($this->keychain->getDecryptedCertificate());

        return $xmlSecurityKey;

    }

}