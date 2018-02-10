<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:42 PM
 */

namespace flipbox\saml\core\services\messages;


use flipbox\saml\core\records\ProviderInterface;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\exceptions\InvalidMetadata;

interface MetadataServiceInterface
{

    /**
     * @param KeyChainRecord|null $withKeyPair
     * @param bool $createKeyFromSettings
     * @return ProviderInterface
     * @throws InvalidMetadata
     * @throws \Exception
     */
    public function create(KeyChainRecord $withKeyPair = null, $createKeyFromSettings = false): ProviderInterface;

}