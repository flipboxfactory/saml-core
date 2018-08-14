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
use LightSaml\Model\Metadata\EntityDescriptor;

interface MetadataServiceInterface
{

    /**
     * @param KeyChainRecord|null $withKeyPair
     * @param null $entityId
     * @return EntityDescriptor
     */
    public function create(KeyChainRecord $withKeyPair = null, $entityId = null): EntityDescriptor;

}