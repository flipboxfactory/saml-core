<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin;

use flipbox\saml\core\records\AbstractProviderIdentity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderIdentityRecord extends AbstractProviderIdentity
{
    public const TABLE_ALIAS = 'saml_test_provider_identity';

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderRecord::class, 'providerId');
    }
}
