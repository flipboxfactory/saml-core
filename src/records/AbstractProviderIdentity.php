<?php

declare(strict_types=1);

namespace flipbox\saml\core\records;

use Carbon\CarbonInterface;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use flipbox\saml\core\records\traits\Ember;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's identity at a provider: the NameID they log in (or are sent) with.
 *
 * Concrete records set `TABLE_ALIAS` and implement {@see provider()}.
 *
 * @property int $id
 * @property int $providerId
 * @property int $userId
 * @property string $nameId
 * @property string|null $sessionId
 * @property bool $enabled
 * @property CarbonInterface $lastLoginDate
 * @property string $uid
 */
abstract class AbstractProviderIdentity extends BaseModel implements ProviderIdentityInterface
{
    use Ember;
    use HasUid;

    protected $attributes = [
        'enabled' => true,
    ];

    private ?User $user = null;

    /**
     * @return BelongsTo<AbstractProvider, $this>
     */
    abstract public function provider(): BelongsTo;

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
            'lastLoginDate' => 'datetime',
        ];
    }

    public function getProvider(): ?AbstractProvider
    {
        return $this->provider()->first();
    }

    public function getUser(): ?User
    {
        if (! $this->userId) {
            return null;
        }

        return $this->user ??= Users::getUserById((int) $this->userId);
    }
}
