<?php

declare(strict_types=1);

namespace flipbox\saml\core\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\MetadataOptions;
use flipbox\saml\core\records\traits\Ember;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;
use SAML2\DOMDocumentFactory;
use SAML2\XML\md\EntityDescriptor;

/**
 * A SAML provider: this system ("my provider") or a partner IdP/SP, described by its metadata.
 *
 * Concrete records set `TABLE_ALIAS` (their table name).
 *
 * @property int $id
 * @property string|null $label
 * @property string $entityId
 * @property string $metadata The provider's metadata XML.
 * @property string $sha256
 * @property string $providerType {@see self::TYPE_IDP} or {@see self::TYPE_SP}
 * @property bool $encryptAssertions
 * @property string|null $encryptionMethod
 * @property int|null $siteId
 * @property string|null $groupOptions JSON; use {@see getGroupOptions()}.
 * @property string|null $metadataOptions JSON; use {@see getMetadataOptions()}.
 * @property bool $syncGroups
 * @property string|null $groupsAttributeName
 * @property string|null $nameIdOverride
 * @property string|null $mapping JSON; use {@see getMapping()}.
 * @property bool $enabled
 * @property string $uid
 * @property-read KeyChainRecord|null $keychain This system's key pair for the provider.
 * @property-read Site|null $site
 */
abstract class AbstractProvider extends BaseModel implements ProviderInterface
{
    use Ember;
    use HasUid;
    use traits\EntityDescriptor;
    use traits\KeyChain;
    use traits\MapUser;

    public const METADATA_HASH_ALGORITHM = 'sha256';

    public const DEFAULT_GROUPS_ATTRIBUTE_NAME = 'groups';

    protected $attributes = [
        'enabled' => true,
        'encryptAssertions' => false,
        'syncGroups' => true,
        'groupsAttributeName' => self::DEFAULT_GROUPS_ATTRIBUTE_NAME,
    ];

    protected ?EntityDescriptor $metadataModel = null;

    abstract public function getLoginPath(): string;

    abstract public function getLogoutPath(): string;

    abstract protected function getDefaultSettings(): AbstractSettings;

    public function getTable(): string
    {
        return static::tableName();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'encryptAssertions' => 'boolean',
            'syncGroups' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $provider): void {
            $provider->prepareForSave();
        });
    }

    /**
     * Encodes the option columns and normalises the metadata, as 5.x did before saving.
     */
    protected function prepareForSave(): void
    {
        if (! $this->entityId) {
            $this->entityId = (string) $this->getEntityId();
        }

        foreach (['mapping', 'groupOptions', 'metadataOptions'] as $column) {
            $value = $this->attributes[$column] ?? null;
            if (is_array($value) || $value instanceof \JsonSerializable) {
                $this->attributes[$column] = json_encode($value);
            }
        }

        $this->sha256 = hash(static::METADATA_HASH_ALGORITHM, (string) $this->metadata);

        if ($model = $this->getMetadataModel()) {
            $this->metadata = (string) $model->toXML()->ownerDocument?->saveXML();
        }
    }

    public function getLoginRequestEndpoint(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGIN_REQUEST_ENDPOINT, $this);
    }

    public function getLoginEndpoint(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGIN_ENDPOINT, $this);
    }

    public function getLogoutRequestEndpoint(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGOUT_REQUEST_ENDPOINT, $this);
    }

    public function getLogoutEndpoint(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGOUT_ENDPOINT, $this);
    }

    /**
     * The path that starts a login with this provider (`/sso/login/request/{uid}`).
     */
    public function getLoginRequestPath(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGIN_REQUEST_ENDPOINT, $this, false);
    }

    /**
     * The path that starts a logout with this provider (`/sso/logout/request/{uid}`).
     */
    public function getLogoutRequestPath(?AbstractSettings $settings = null): string
    {
        return UrlHelper::buildEndpointUrl($settings ?? $this->getDefaultSettings(), UrlHelper::LOGOUT_REQUEST_ENDPOINT, $this, false);
    }

    public function generateUid(): void
    {
        $this->uid ??= Str::uuid()->toString();
    }

    public function getEntityId(): ?string
    {
        return $this->getMetadataModel()?->getEntityID() ?? ($this->attributes['entityId'] ?? null);
    }

    /**
     * The parsed metadata. Parsing is strict and fails closed: invalid XML throws.
     */
    public function getMetadataModel(): ?EntityDescriptor
    {
        if (! $this->metadataModel && $this->metadata) {
            $this->metadataModel = new EntityDescriptor(DOMDocumentFactory::fromString($this->metadata)->documentElement);
        }

        return $this->metadataModel;
    }

    public function setMetadataModel(EntityDescriptor $descriptor): static
    {
        $this->metadataModel = $descriptor;

        return $this;
    }

    /**
     * Sets the metadata XML, dropping any parsed model of the old XML.
     */
    public function setMetadataXml(?string $xml): static
    {
        $this->metadata = (string) $xml;
        $this->metadataModel = null;
        $this->idpSsoDescriptors = [];
        $this->spSsoDescriptors = [];

        return $this;
    }

    public function isIdentityProvider(): bool
    {
        return $this->providerType === static::TYPE_IDP;
    }

    public function isServiceProvider(): bool
    {
        return $this->providerType === static::TYPE_SP;
    }

    public function getType(): ?string
    {
        return $this->providerType;
    }

    /**
     * @return HasOne<LinkRecord, $this>
     */
    public function link(): HasOne
    {
        return $this->hasOne(LinkRecord::class, 'providerUid', 'uid');
    }

    /**
     * @return HasOneThrough<KeyChainRecord, LinkRecord, $this>
     */
    public function keychain(): HasOneThrough
    {
        // By UID: saml-sp and saml-idp share the link table, and their provider IDs overlap
        return $this->hasOneThrough(KeyChainRecord::class, LinkRecord::class, 'providerUid', 'id', 'uid', 'keyChainId');
    }

    public function getKeychain(): ?KeyChainRecord
    {
        return $this->keychain;
    }

    /**
     * Sets this system's key pair for the provider. Saving with {@see \flipbox\saml\core\services\AbstractProviderService::save()}
     * links it.
     */
    public function setKeychain(?KeyChainRecord $keyChain): static
    {
        $this->setRelation('keychain', $keyChain);

        return $this;
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'siteId');
    }

    public function setSite(?Site $site): static
    {
        $this->siteId = $site?->id;
        $this->setRelation('site', $site);

        return $this;
    }

    public function getSiteModel(): ?SiteData
    {
        return $this->siteId ? Sites::getSiteById($this->siteId) : null;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $mapping
     */
    public function setMapping(array $mapping): static
    {
        $this->attributes['mapping'] = json_encode(array_values($mapping));

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMapping(): array
    {
        $mapping = $this->hasMapping() ? json_decode((string) $this->attributes['mapping'], true) : [];

        return is_array($mapping) ? array_values($mapping) : [];
    }

    public function setMetadataOptions(MetadataOptions $metadataOptions): static
    {
        $this->attributes['metadataOptions'] = json_encode($metadataOptions);

        return $this;
    }

    public function getMetadataOptions(): MetadataOptions
    {
        return $this->hasMetadataOptions()
            ? MetadataOptions::jsonUnserialize((string) $this->attributes['metadataOptions'])
            : new MetadataOptions;
    }

    public function setGroupOptions(GroupOptions $groupOptions): static
    {
        $this->attributes['groupOptions'] = json_encode($groupOptions);

        return $this;
    }

    public function getGroupOptions(): GroupOptions
    {
        return $this->hasGroupOptions()
            ? GroupOptions::jsonUnserialize((string) $this->attributes['groupOptions'])
            : new GroupOptions;
    }

    public function hasMapping(): bool
    {
        return $this->hasJsonProperty('mapping');
    }

    public function hasGroupOptions(): bool
    {
        return $this->hasJsonProperty('groupOptions');
    }

    public function hasMetadataOptions(): bool
    {
        return $this->hasJsonProperty('metadataOptions');
    }

    protected function hasJsonProperty(string $property): bool
    {
        $value = $this->attributes[$property] ?? null;

        if (! is_string($value) || $value === '') {
            return false;
        }

        json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
