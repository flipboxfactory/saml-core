<?php

namespace flipbox\saml\core\records;

use flipbox\ember\records\ActiveRecord;
use flipbox\keychain\records\KeyChainRecord;
use SAML2\DOMDocumentFactory;
use SAML2\XML\md\EntityDescriptor;
use yii\db\ActiveQuery;

/**
 * Class AbstractProvider
 * @package flipbox\saml\core\records
 */
abstract class AbstractProvider extends ActiveRecord implements ProviderInterface
{

    use traits\EntityDescriptor, traits\KeyChain;

    const METADATA_HASH_ALGORITHM = 'sha256';
    const DEFAULT_GROUPS_ATTRIBUTE_NAME = 'groups';

    protected $metadataModel;
    protected $cachedKeychain;

    /**
     * This method helps initiate the login process for a remote provider.
     * When using this method, say your craft site is the SP. This method will be helpful
     * on the IDP provider record. Going to this login path will
     * initiate the login process for this IDP. Returns null when you getLoginPath for the
     * local provider (the current craft site).
     *
     * @return string|null
     */
    abstract public function getLoginPath();

    /**
     * Similar to getLoginPath(), this method initiates logout with the intended remote
     * provider.
     *
     * @return string|null
     */
    abstract public function getLogoutPath();

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (! $this->entityId) {
            $this->entityId = $this->getEntityId();
        }

        if (is_array($this->mapping)) {
            $this->mapping = \json_encode($this->mapping);
        }

        if (is_array($this->denyGroupAccess)) {
            $this->denyGroupAccess = \json_encode($this->denyGroupAccess);
        }

        $this->sha256 = hash(static::METADATA_HASH_ALGORITHM, $this->metadata);

        $this->metadata = $this->getMetadataModel()->toXML()->ownerDocument->saveXML();

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
    public function getMetadataModel()
    {

        if (! $this->metadataModel && $this->metadata) {
            $this->metadataModel = new EntityDescriptor(
                DOMDocumentFactory::fromString($this->metadata)->documentElement
            );
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
     * @return bool
     */
    public function isIdentityProvider()
    {
        return $this->providerType === static::TYPE_IDP;
    }

    /**
     * @return bool
     */
    public function isServiceProvider()
    {
        return $this->providerType === static::TYPE_SP;
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

    /**
     * @return array
     */
    public function getMapping()
    {
        if (is_string($this->mapping)) {
            $this->mapping = json_decode($this->mapping, true);
        }

        return $this->mapping;
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function setDenyGroupAccess(array $ids)
    {
        $this->denyGroupAccess = array_filter($ids, function ($id) {
            return is_numeric($id);
        });

        return $this;
    }

    /**
     * @return array
     */
    public function getDenyGroupAccess()
    {
        if (is_string($this->denyGroupAccess)) {
            $this->denyGroupAccess = json_decode($this->denyGroupAccess, true);
        }

        return $this->denyGroupAccess;
    }

    /**
     * @param $groupId
     * @return bool
     */
    public function hasDenyGroupAccess($groupId): bool
    {
        if (! is_array($this->getDenyGroupAccess())) {
            return false;
        }

        return in_array($groupId, $this->getDenyGroupAccess());
    }
}
