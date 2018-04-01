<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/28/18
 * Time: 9:37 PM
 */

namespace flipbox\saml\core\services;


use craft\elements\User;
use flipbox\saml\core\records\AbstractProviderIdentity;
use flipbox\saml\core\records\ProviderIdentityInterface;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\base\Component;
use yii\helpers\Json;

abstract class AbstractProviderIdentityService extends Component implements ProviderIdentityServiceInterface
{
    use EnsureSamlPlugin;

    /**
     * @return string
     */
    abstract public function getRecordClass();

    /**
     * @param string $nameId
     * @param ProviderInterface $provider
     * @return ProviderIdentityInterface
     */
    public function findByNameId(string $nameId, ProviderInterface $provider): ProviderIdentityInterface
    {
        return $this->find([
            'nameId'     => $nameId,
            'providerId' => $provider->id,
        ]);
    }

    /**
     * @param User $user
     * @return ProviderIdentityInterface
     */
    public function findByUser(User $user): ProviderIdentityInterface
    {
        return $this->find([
            'userId' => $user->getId(),
        ]);
    }

    /**
     * @param array $condition
     * @return ProviderIdentityInterface
     */
    public function find($condition = []): ProviderIdentityInterface
    {
        /** @var AbstractProviderIdentity $class */
        $class = $this->getRecordClass();

        /** @var AbstractProviderIdentity $class */
        $providerId = $class::find()->where($condition)->one();

        return $providerId;
    }

    /**
     * @param ProviderIdentityInterface $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return ProviderIdentityInterface
     * @throws \Exception
     */
    public function save(ProviderIdentityInterface $record, $runValidation = true, $attributeNames = null): ProviderIdentityInterface
    {

        if (! $record->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }

        return $record;
    }
}