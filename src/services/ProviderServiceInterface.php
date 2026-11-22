<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Database\Eloquent\Builder;
use SAML2\XML\md\EntityDescriptor;

interface ProviderServiceInterface
{
    /**
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProvider>
     */
    public function find(array $condition = []): Builder;

    public function findOwn(): ?AbstractProvider;

    public function create(EntityDescriptor $entityDescriptor, ?KeyChainRecord $keyChainRecord = null): AbstractProvider;

    public function save(AbstractProvider $record): AbstractProvider;

    public function delete(AbstractProvider $provider): bool;
}
