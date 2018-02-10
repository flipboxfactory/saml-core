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
//
//    /**
//     * @var null|EntityDescriptor
//     */
//    private $metadataModel;

    public function beforeSave($insert)
    {
        if (! $this->entityId) {
            $this->entityId = $this->getEntityId();
        }
        $this->sha256 = hash(static::METADATA_HASH_ALGO, $this->metadata);

        return parent::beforeSave($insert);
    }

    public function getEntityId()
    {
        $metadata = $this->getMetadata();
        return $metadata->getEntityID();
    }

    public function getMetadata(): EntityDescriptor
    {
        if (! $this->metadataModel) {
            $this->metadataModel = EntityDescriptor::loadXml($this->metadata);
        }

        return $this->metadataModel;
    }
}