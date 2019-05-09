<?php

namespace flipbox\saml\core\records;

use flipbox\ember\records\ActiveRecord;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\models\GroupOptions;
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

        if ($this->groupOptions instanceof GroupOptions) {
            $this->serializeGroupOptions();
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
        if (is_string($this->mapping) && $this->hasMapping()) {
            $this->mapping = json_decode($this->mapping, true);
        }

        return $this->mapping;
    }

    /**
     * @param GroupOptions $groupOptions
     * @return $this
     */
    public function setGroupOptions(GroupOptions $groupOptions)
    {
        $this->groupOptions = $groupOptions;

        return $this;
    }

    public function getGroupOptions(): GroupOptions
    {
        if ($this->hasGroupOptions()) {
            $this->unserializeGroupOptions();
        } elseif (! $this->groupOptions) {
            $this->groupOptions = new GroupOptions();
        }

        return $this->groupOptions;
    }

    /**
     * @return false|string
     */
    protected function serializeGroupOptions()
    {
        return $this->groupOptions = json_encode($this->groupOptions);
    }

    /**
     * @return GroupOptions|string
     */
    protected function unserializeGroupOptions()
    {
        if ($this->hasGroupOptions()) {
            $this->groupOptions = new GroupOptions(json_decode($this->groupOptions, true));
        }
        return $this->groupOptions;
    }

    /**
     * @return bool
     */
    protected function hasMapping()
    {
        return $this->hasJsonProperty('mapping');
    }

    /**
     * @return bool
     */
    protected function hasGroupOptions(): bool
    {
        return $this->hasJsonProperty('groupOptions');
    }

    /**
     * @param string $property
     * @return bool
     */
    protected function hasJsonProperty(string $property)
    {
        if (! $this->{$property}) {
            return false;
        }
        try {
            json_decode($this->{$property}, true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
