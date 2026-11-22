<?php

declare(strict_types=1);

namespace flipbox\saml\core\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a provider to this system's key pair for it. Shared by saml-sp and saml-idp.
 *
 * @property int $id
 * @property int $providerId
 * @property string $providerUid
 * @property int $keyChainId
 */
class LinkRecord extends BaseModel
{
    use HasUid;
    use traits\Ember;

    public const TABLE_ALIAS = 'saml_provider_keychain_link';

    protected $table = self::TABLE_ALIAS;

    /**
     * @return BelongsTo<KeyChainRecord, $this>
     */
    public function keyChain(): BelongsTo
    {
        return $this->belongsTo(KeyChainRecord::class, 'keyChainId');
    }
}
