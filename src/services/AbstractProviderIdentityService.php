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

/**
 * Class AbstractProviderIdentityService
 * @package flipbox\saml\core\services
 * @property \DateTime $lastLoginDate
 */
abstract class AbstractProviderIdentityService extends Component implements ProviderIdentityServiceInterface
{
    use EnsureSamlPlugin;

    /**
     * @return string
     */
    abstract public function getRecordClass();

    /**
     * @inheritdoc
     */
    public function findByNameId(string $nameId, ProviderInterface $provider)
    {
        return $this->find([
            'nameId'     => $nameId,
            'providerId' => $provider->id,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function findByUser(User $user)
    {
        return $this->find([
            'userId' => $user->getId(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function find($condition = [])
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
     * @param array|null $attributeNames
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