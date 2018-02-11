<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:51 PM
 */

namespace flipbox\saml\core\records;

use flipbox\ember\helpers\ModelHelper;
use flipbox\ember\records\ActiveRecord;
use LightSaml\Model\Metadata\EntityDescriptor;

abstract class AbstractProvider extends ActiveRecord
{
    const METADATA_HASH_ALGO = 'sha256';

    protected $metadataModel;

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
}