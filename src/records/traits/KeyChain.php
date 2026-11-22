<?php

declare(strict_types=1);

namespace flipbox\saml\core\records\traits;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\exceptions\InvalidMetadata;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * @property-read KeyChainRecord|null $keychain
 */
trait KeyChain
{
    public function defaultCipherType(): string
    {
        return XMLSecurityKey::RSA_SHA256;
    }

    /**
     * This system's private key for signing messages to the provider.
     *
     * @throws InvalidMetadata if the provider has no key pair
     */
    public function keychainPrivateXmlSecurityKey(): XMLSecurityKey
    {
        $keychain = $this->keychain ?? throw new InvalidMetadata('This provider has no key pair to sign with.');

        $xmlSecurityKey = new XMLSecurityKey($this->defaultCipherType(), ['type' => 'private']);
        $xmlSecurityKey->loadKey($keychain->getDecryptedKey());

        return $xmlSecurityKey;
    }
}
