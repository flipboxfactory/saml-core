<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Plugins;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\tests\fixtures\plugin\ProviderIdentityRecord;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;
use Illuminate\Support\Facades\Schema;

it('installs the SAML tables and KeyChain', function () {
    expect(Plugins::isPluginInstalled('keychain'))->toBeTrue();

    foreach ([ProviderRecord::TABLE_ALIAS, ProviderIdentityRecord::TABLE_ALIAS, LinkRecord::TABLE_ALIAS] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing $table");
    }
});
