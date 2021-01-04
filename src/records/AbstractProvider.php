<?php

namespace flipbox\saml\core\records;

use craft\db\ActiveRecord;
use craft\helpers\StringHelper;
use craft\records\Site;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\MetadataOptions;
use flipbox\saml\core\records\traits\Ember;
use SAML2\DOMDocumentFactory;
use SAML2\XML\md\EntityDescriptor;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use flipbox\saml\core\models\AbstractSettings;

/**
 * Class AbstractProvider
 * @package flipbox\saml\core\records
 */
abstract class AbstractProvider extends ActiveRecord implements ProviderInterface
{

    use traits\EntityDescriptor, traits\KeyChain, traits\MapUser, Ember;

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

    abstract protected function getDefaultSettings():AbstractSettings;

    public function getLoginRequestEndpoint(AbstractSettings $settings = null)
    {
        return UrlHelper::buildEndpointUrl(
            $settings ?? $this->getDefaultSettings(),
            UrlHelper::LOGIN_REQUEST_ENDPOINT,
            $this,
            true
        );
    }

    public function getLoginEndpoint(AbstractSettings $settings = null)
    {
        return UrlHelper::buildEndpointUrl(
            $settings ?? $this->getDefaultSettings(),
            UrlHelper::LOGIN_ENDPOINT,
            $this,
            true
        );
    }

    public function getLogoutRequestEndpoint(AbstractSettings $settings = null)
    {
        return UrlHelper::buildEndpointUrl(
            $settings ?? $this->getDefaultSettings(),
            UrlHelper::LOGOUT_REQUEST_ENDPOINT,
            $this,
            true
        );
    }

    public function getLogoutEndpoint(AbstractSettings $settings = null)
    {
        return UrlHelper::buildEndpointUrl(
            $settings ?? $this->getDefaultSettings(),
            UrlHelper::LOGOUT_ENDPOINT,
            $this,
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        parent::loadDefaultValues($skipIfSet);

        // fix the issue with postgres pulling zero as default
        if (!trim($this->uid)) {
            $this->generateUid();
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function generateUid()
    {
        if (!$this->uid) {
            $this->uid = StringHelper::UUID();
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (! $this->entityId) {
            $this->entityId = $this->getEntityId();
        }

        if (is_array($this->mapping)) {
            $this->mapping = json_encode($this->mapping);
        }

        if ($this->groupOptions instanceof GroupOptions) {
            $this->groupOptions = json_encode($this->groupOptions);
        }

        if ($this->metadataOptions instanceof MetadataOptions) {
            $this->metadataOptions = json_encode($this->metadataOptions);
        }

        $this->sha256 = hash(static::METADATA_HASH_ALGORITHM, $this->metadata);

        $this->metadata = $this->getMetadataModel()->toXML()->ownerDocument->saveXML();

        if ($this->site instanceof Site) {
            $this->siteId = $this->site->id;
        }

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
     *
     */
    public function setSite(Site $site)
    {
        $this->populateRelation('site', $site);
        return $this;
    }

    /**
     * Returns the provider's site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return \craft\models\Site|null
     */
    public function getSiteModel()
    {
        $site = $this->getSite()->one();

        if ($site instanceof Site) {
            return new \craft\models\Site($site);
        }

        return null;
    }

    /**
     * @param array $mapping
     * @return $this
     */
    public function setMapping(array $mapping)
    {
        $this->mapping = array_values($mapping);

        return $this;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        $mapping = [];
        if ($this->hasMapping()) {
            $mapping = json_decode($this->mapping, true);
        }

        return $mapping;
    }

    public function setMetadataOptions(MetadataOptions $metadataOptions)
    {
        $this->metadataOptions = $metadataOptions;

        return $this;
    }

    public function getMetadataOptions(): MetadataOptions
    {
        $metadataOptions = new MetadataOptions();
        if ($this->hasMetadataOptions()) {
            $metadataOptions = MetadataOptions::jsonUnserialize($this->metadataOptions);
        }

        return $metadataOptions;
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
        $groupOptions = new GroupOptions();
        if ($this->hasGroupOptions()) {
            $groupOptions = GroupOptions::jsonUnserialize($this->groupOptions);
        }

        return $groupOptions;
    }

    /**
     * @return bool
     */
    public function hasMapping()
    {
        return $this->hasJsonProperty('mapping');
    }

    /**
     * @return bool
     */
    public function hasGroupOptions(): bool
    {
        return $this->hasJsonProperty('groupOptions');
    }

    /**
     * @return bool
     */
    public function hasMetadataOptions(): bool
    {
        return $this->hasJsonProperty('metadataOptions');
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
