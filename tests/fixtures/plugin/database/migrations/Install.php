<?php

declare(strict_types=1);

use flipbox\saml\core\migrations\AbstractInstall;
use flipbox\saml\core\tests\fixtures\plugin\ProviderIdentityRecord;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;

return new class extends AbstractInstall
{
    protected function getProviderTableName(): string
    {
        return ProviderRecord::TABLE_ALIAS;
    }

    protected function getIdentityTableName(): string
    {
        return ProviderIdentityRecord::TABLE_ALIAS;
    }
};
