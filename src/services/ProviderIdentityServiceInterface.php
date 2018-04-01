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

interface ProviderIdentityServiceInterface
{
    /**
     * @param string $nameId
     * @param ProviderInterface $provider
     * @return ProviderIdentityInterface
     */
    public function findByNameId(string $nameId, ProviderInterface $provider): ProviderIdentityInterface;

    /**
     * @param User $user
     * @return ProviderIdentityInterface
     */
    public function findByUser(User $user): ProviderIdentityInterface;

    /**
     * @param array $condition
     * @return ProviderIdentityInterface
     */
    public function find($condition = []): ProviderIdentityInterface;

    /**
     * @param ProviderIdentityInterface $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return ProviderIdentityInterface
     */
    public function save(ProviderIdentityInterface $record, $runValidation = true, $attributeNames = null): ProviderIdentityInterface;
}