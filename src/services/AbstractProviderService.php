<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\records\ProviderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use SAML2\XML\md\EntityDescriptor;

/**
 * Finds and saves the plugin's provider records.
 */
abstract class AbstractProviderService extends PluginService implements ProviderServiceInterface
{
    /**
     * This system's own provider record.
     */
    abstract public function findOwn(): ?AbstractProvider;

    /**
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProvider>
     */
    public function find(array $condition = []): Builder
    {
        $class = $this->getPlugin()->getProviderRecordClass();

        return $class::query()->where($condition);
    }

    /**
     * Enabled identity providers.
     *
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProvider>
     */
    public function findByIdp(array $condition = []): Builder
    {
        return $this->findByType(ProviderInterface::TYPE_IDP, $condition);
    }

    /**
     * Enabled service providers.
     *
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProvider>
     */
    public function findBySp(array $condition = []): Builder
    {
        return $this->findByType(ProviderInterface::TYPE_SP, $condition);
    }

    /**
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProvider>
     */
    protected function findByType(string $type, array $condition = []): Builder
    {
        if (! in_array($type, [ProviderInterface::TYPE_SP, ProviderInterface::TYPE_IDP], true)) {
            throw new InvalidArgumentException('Type must be idp or sp.');
        }

        return $this->find([
            'enabled' => true,
            'providerType' => $type,
            ...$condition,
        ]);
    }

    /**
     * @return Builder<AbstractProvider>
     */
    public function findByEntityId(string $entityId): Builder
    {
        return $this->find(['entityId' => $entityId]);
    }

    /**
     * A new, unsaved provider record of this plugin's type for the metadata.
     */
    public function create(EntityDescriptor $entityDescriptor, ?KeyChainRecord $keyChainRecord = null): AbstractProvider
    {
        $recordClass = $this->getPlugin()->getProviderRecordClass();

        $provider = new $recordClass;
        $provider->generateUid();
        $provider->providerType = (string) $this->getPlugin()->getMyType();
        $provider->setMetadataModel($entityDescriptor);
        $provider->entityId = (string) $entityDescriptor->getEntityID();
        $provider->metadata = $provider->toXmlString();

        if ($keyChainRecord) {
            $provider->setKeychain($keyChainRecord);
        }

        return $provider;
    }

    /**
     * Saves a provider and links it to its key pair, in one transaction.
     *
     * @throws RuntimeException if it can't be saved
     */
    public function save(AbstractProvider $record): AbstractProvider
    {
        DB::transaction(function () use ($record) {
            if (! $record->save()) {
                throw new RuntimeException('The provider couldn’t be saved.');
            }

            if ($record->relationLoaded('keychain') && $record->keychain) {
                $this->linkToKey($record, $record->keychain);
            }
        });

        return $record;
    }

    public function linkToKey(AbstractProvider $provider, KeyChainRecord $keyChain): void
    {
        if (! $provider->id || ! $keyChain->id) {
            throw new RuntimeException('The provider and key pair must be saved before they’re linked.');
        }

        // By UID: saml-sp and saml-idp share the link table, and their provider IDs overlap
        LinkRecord::query()->updateOrCreate(
            ['providerUid' => $provider->uid],
            ['providerId' => $provider->id, 'keyChainId' => $keyChain->id],
        );
    }

    public function delete(AbstractProvider $provider): bool
    {
        return (bool) DB::transaction(function () use ($provider) {
            LinkRecord::query()->where('providerUid', $provider->uid)->delete();

            return $provider->delete();
        });
    }
}
