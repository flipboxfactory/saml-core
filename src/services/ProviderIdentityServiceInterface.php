<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:42 PM
 */

namespace flipbox\saml\core\services;


use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\records\ProviderIdentityInterface;
use craft\elements\User;
use yii\db\Query;

interface ProviderIdentityServiceInterface
{
    /**
     * @param string $nameId
     * @param ProviderInterface $provider
     * @return Query|null
     */
    public function findByNameId(string $nameId, ProviderInterface $provider);

    /**
     * @param User $user
     * @return Query|null
     */
    public function findByUser(User $user);

    /**
     * @param array $condition
     * @return Query|null
     */
    public function find($condition = []);

    /**
     * @param ProviderIdentityInterface $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return ProviderIdentityInterface
     */
    public function save(ProviderIdentityInterface $record, $runValidation = true, $attributeNames = null): ProviderIdentityInterface;
}