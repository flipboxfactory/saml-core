<?php

declare(strict_types=1);

namespace flipbox\saml\core\migrations;

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Plugins;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Base for the SAML plugins' install migrations (`database/migrations/Install.php`).
 *
 * Creates the plugin's provider and provider identity tables, and the provider/key pair link table that saml-sp and
 * saml-idp share. Upgrades from 5.x already have these tables (5.x's incremental migrations are folded in here), so
 * this only runs on a fresh install, and skips tables that exist.
 */
abstract class AbstractInstall extends Migration
{
    abstract protected function getProviderTableName(): string;

    abstract protected function getIdentityTableName(): string;

    public function up(): void
    {
        $this->installKeyChain();

        if (! Schema::hasTable($this->getProviderTableName())) {
            Schema::create($this->getProviderTableName(), fn (Blueprint $table) => $this->defineProviderTable($table));
        }

        if (! Schema::hasTable(LinkRecord::TABLE_ALIAS)) {
            Schema::create(LinkRecord::TABLE_ALIAS, function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('providerId');
                $table->char('providerUid', 36);
                $this->referenceColumn($table, 'keyChainId', KeyChainRecord::TABLE);
                $table->foreign('keyChainId')->references('id')->on(KeyChainRecord::TABLE)->cascadeOnDelete();
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->unique(['providerUid', 'keyChainId']);
            });
        }

        if (! Schema::hasTable($this->getIdentityTableName())) {
            Schema::create($this->getIdentityTableName(), function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('providerId');
                $table->foreign('providerId')->references('id')->on($this->getProviderTableName())->cascadeOnDelete();
                $this->referenceColumn($table, 'userId', Table::USERS);
                $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete();
                $table->string('nameId');
                $table->string('sessionId')->nullable();
                $table->boolean('enabled')->default(true);
                $table->dateTime('lastLoginDate');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
                $table->index('nameId');
                $table->unique(['providerId', 'nameId', 'userId']);
            });
        }
    }

    protected function defineProviderTable(Blueprint $table): void
    {
        // INT ids, as in 5.x and Craft's own tables
        $table->integer('id', true);
        $table->string('label', 64)->nullable();
        $table->string('entityId')->index();
        $table->mediumText('metadata');
        $table->string('sha256')->unique();
        $table->enum('providerType', [SettingsInterface::SP, SettingsInterface::IDP]);
        $table->boolean('encryptAssertions')->default(false);
        $table->string('encryptionMethod', 64)->nullable();
        $this->referenceColumn($table, 'siteId', Table::SITES)->nullable();
        $table->foreign('siteId')->references('id')->on(Table::SITES)->nullOnDelete();
        $table->text('groupOptions')->nullable();
        $table->text('metadataOptions')->nullable();
        $table->boolean('syncGroups')->default(true);
        $table->string('groupsAttributeName', 64)->nullable()->default(AbstractProvider::DEFAULT_GROUPS_ATTRIBUTE_NAME);
        $table->text('nameIdOverride')->nullable();
        $table->text('mapping')->nullable();
        $table->boolean('enabled')->default(true);
        $table->dateTime('dateCreated');
        $table->dateTime('dateUpdated');
        $table->char('uid', 36)->default('0');
    }

    /**
     * A column of the same integer type as `$referencedTable.id`, so MySQL accepts a foreign key to it. (KeyChain's
     * `id` is INT on sites upgraded from Craft 5, and may differ on fresh installs.)
     */
    protected function referenceColumn(Blueprint $table, string $column, string $referencedTable): ColumnDefinition
    {
        $id = collect(Schema::getColumns($referencedTable))->firstWhere('name', 'id') ?? [];
        $typeName = strtolower((string) ($id['type_name'] ?? 'int'));
        $unsigned = str_contains(strtolower((string) ($id['type'] ?? '')), 'unsigned');
        $big = in_array($typeName, ['bigint', 'int8', 'bigserial'], true);

        return match (true) {
            $big && $unsigned => $table->unsignedBigInteger($column),
            $big => $table->bigInteger($column),
            $unsigned => $table->unsignedInteger($column),
            default => $table->integer($column),
        };
    }

    public function down(): void
    {
        Schema::dropIfExists($this->getIdentityTableName());

        // The link table is shared with the other SAML plugin: remove only this plugin's links
        if (Schema::hasTable(LinkRecord::TABLE_ALIAS) && Schema::hasTable($this->getProviderTableName())) {
            DB::table(LinkRecord::TABLE_ALIAS)
                ->whereIn('providerUid', DB::table($this->getProviderTableName())->select('uid'))
                ->delete();

            if (! DB::table(LinkRecord::TABLE_ALIAS)->exists()) {
                Schema::drop(LinkRecord::TABLE_ALIAS);
            }
        }

        Schema::dropIfExists($this->getProviderTableName());
    }

    protected function installKeyChain(): void
    {
        if (! Plugins::isPluginInstalled('keychain')) {
            Plugins::installPlugin('keychain');
        }
    }
}
