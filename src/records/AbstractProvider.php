<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:51 PM
 */

namespace flipbox\saml\core\records;

use flipbox\ember\records\ActiveRecord;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\SerializeHelper;
use LightSaml\Model\Metadata\EntityDescriptor;
use yii\db\ActiveQuery;

/**
 * Class AbstractProvider
 * @package flipbox\saml\core\records
 * @property int $entityId
 * @property string $sha256
 * @property string $metadata
 */
abstract class AbstractProvider extends ActiveRecord
{
    const METADATA_HASH_ALGO = 'sha256';

    protected $metadataModel;
    protected $cachedKeychain;

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (! $this->entityId) {
            $this->entityId = $this->getEntityId();
        }
        $this->sha256 = hash(static::METADATA_HASH_ALGO, $this->metadata);

        $this->metadata = SerializeHelper::toXml($this->getMetadataModel());

        return parent::beforeSave($insert);
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        $metadata = $this->getMetadataModel();
        return $metadata->getEntityID();
    }

    /**
     * @return EntityDescriptor
     */
    public function getMetadataModel(): EntityDescriptor
    {
        if (! $this->metadataModel) {
            $this->metadataModel = EntityDescriptor::loadXml($this->metadata);
        }

        return $this->metadataModel;
    }

    /**
     * @param EntityDescriptor $descriptor
     * @return $this
     */
    public function setMetadataModel(EntityDescriptor $descriptor)
    {
        $this->metadataModel = $descriptor;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->providerType;
    }

    /**
     * @return ActiveQuery
     */
    public function getLink()
    {
        return $this->hasOne(LinkRecord::class, [
            'providerId' => 'id',
        ]);
    }

    /**
     * @return ActiveQuery
     */
    public function getKeychain()
    {
        return $this->hasOne(KeyChainRecord::class, [
            'id' => 'keyChainId',
        ])->viaTable(LinkRecord::tableName(), [
            'providerId' => 'id',
        ]);
    }

    /**
     * @param KeyChainRecord $keyChain
     * @return AbstractProvider
     */
    public function setKeychain(KeyChainRecord $keyChain)
    {
        $this->populateRelation('keychain', $keyChain);
        return $this;
    }
}