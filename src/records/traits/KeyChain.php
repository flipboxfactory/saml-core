<?php


namespace flipbox\saml\core\records\traits;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Trait KeyPair
 * @package flipbox\saml\core\records\traits
 * @property KeyChainRecord $keychain
 * @mixin AbstractProvider
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
}
