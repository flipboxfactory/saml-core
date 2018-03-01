<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/28/18
 * Time: 9:37 PM
 */

namespace flipbox\saml\core\services;


use flipbox\saml\core\records\AbstractProviderIdentity;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use flipbox\saml\sp\records\ProviderIdentityRecord;
use yii\base\Component;
use yii\helpers\Json;

abstract class AbstractProviderIdentityService extends Component
{
    use EnsureSamlPlugin;

    /**
     * @return string
     */
    abstract public function getRecordClass();

    /**
     * @param string $nameId
     * @return null|AbstractProviderIdentity
     */
    public function findByNameId(string $nameId, ProviderInterface $provider)
    {
        return $this->find([
            'nameId'     => $nameId,
            'providerId' => $provider->id,
        ]);
    }

    /**
     * @param array $condition
     * @return null|AbstractProviderIdentity
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
     * @param ProviderIdentityRecord $record
     * @return ProviderIdentityRecord
     * @throws \Exception
     */
    public function save(ProviderIdentityRecord $record, $runValidation = true, $attributeNames = null)
    {

        if (! $record->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }

        return $record;
    }
}