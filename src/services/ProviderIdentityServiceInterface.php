<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use flipbox\saml\core\records\AbstractProviderIdentity;
use Illuminate\Database\Eloquent\Builder;

interface ProviderIdentityServiceInterface
{
    /**
     * @param  array<string, mixed>  $condition
     * @return Builder<AbstractProviderIdentity>
     */
    public function find(array $condition = []): Builder;

    public function save(AbstractProviderIdentity $record): AbstractProviderIdentity;
}
