<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:42 PM
 */

namespace flipbox\saml\core\services;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use SAML2\XML\md\EntityDescriptor;
use yii\db\Query;

interface ProviderServiceInterface
{

    /**
     * @param EntityDescriptor $entityDescriptor
     * @param KeyChainRecord|null $keyChainRecord
     * @return ProviderInterface
     */
    public function create(
        EntityDescriptor $entityDescriptor,
        ?KeyChainRecord $keyChainRecord = null
    ): ProviderInterface;

    /**
     * @param AbstractProvider $record
     * @param bool $runValidation
     * @param array|null $attributeNames
     * @return AbstractProvider
     * @throws \Exception
     */
    public function save(ProviderInterface $record, $runValidation = true, $attributeNames = null);

    /**
     * @param ProviderInterface $provider
     * @return bool|int
     */
    public function delete(ProviderInterface $record);

    /**
     * @param AbstractProvider $provider
     * @param KeyChainRecord $keyChain
     * @param bool $runValidation
     * @param array|null $attributeNames
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
     * @return Query|null
     */
    public function findByEntityId($entityId);

    /**
     * @return AbstractProvider|null
     */
    public function findOwn();

    /**
     * @return Query|null
     */
    public function findByIdp($condition = []);

    /**
     * @return Query|null
     */
    public function findBySp($condition = []);
}
