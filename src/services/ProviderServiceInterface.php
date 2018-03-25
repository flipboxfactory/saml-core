<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:42 PM
 */

namespace flipbox\saml\core\services;


use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\keychain\records\KeyChainRecord;

interface ProviderServiceInterface
{

    /**
     * @return string
     */
    public function getRecordClass();

    /**
     * @param AbstractProvider $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return AbstractProvider
     * @throws \Exception
     */
    public function save(AbstractProvider $record, $runValidation = true, $attributeNames = null);

    /**
     * @param AbstractProvider $provider
     * @param KeyChainRecord $keyChain
     * @param bool $runValidation
     * @param null $attributeNames
     * @throws \Exception
     */
    public function linkToKey(
        AbstractProvider $provider,
        KeyChainRecord $keyChain,
        $runValidation = true,
        $attributeNames = null
    );

    /**
     * @param string $entityId
     * @return ProviderInterface
     */
    public function findByEntityId($entityId);


    /**
     * @return AbstractProvider
     */
    public function findOwn(): AbstractProvider;
}