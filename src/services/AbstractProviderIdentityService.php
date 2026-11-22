<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use CraftCms\Cms\User\Elements\User;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Finds and saves users' identities at providers.
 */
abstract class AbstractProviderIdentityService extends PluginService implements ProviderIdentityServiceInterface
{
    /**
     * @return Builder<AbstractProviderIdentity>
     */
    public function findByNameId(string $nameId, AbstractProvider $provider): Builder
    {
        return $this->find(['nameId' => $nameId, 'providerId' => $provider->id]);
    }

    /**
     * @return Builder<AbstractProviderIdentity>
     */
    public function findByUser(User $user): Builder
    {
        return $this->find(['userId' => $user->id]);
    }

    public function findByUserAndProvider(User $user, AbstractProvider $provider): ?AbstractProviderIdentity
    {
        return $this->find(['userId' => $user->id, 'providerId' => $provider->id])->first();
    }

    /**
     * The user's identity at the provider, or a new unsaved one using the provider's NameID for them.
     */
    public function findByUserAndProviderOrCreate(User $user, AbstractProvider $provider): AbstractProviderIdentity
    {
        if ($identity = $this->findByUserAndProvider($user, $provider)) {
            return $identity;
        }

        $recordClass = $this->getPlugin()->getProviderIdentityRecordClass();

        return new $recordClass([
            'nameId' => $provider->assignNameId($user),
            'providerId' => $provider->id,
            'enabled' => true,
            'userId' => $user->id,
            'lastLoginDate' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProviderIdentity>
     */
    public function find(array $condition = []): Builder
    {
        $class = $this->getPlugin()->getProviderIdentityRecordClass();

        return $class::query()->where($condition);
    }

    /**
     * @throws RuntimeException if it can't be saved
     */
    public function save(AbstractProviderIdentity $record): AbstractProviderIdentity
    {
        if (! $record->save()) {
            throw new RuntimeException('The provider identity couldn’t be saved.');
        }

        return $record;
    }
}
