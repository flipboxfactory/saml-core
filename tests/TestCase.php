<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests;

use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\Testing\PluginTestCase;
use CraftCms\Cms\User\Models\User;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * Boots Craft with KeyChain and a fixture SAML plugin (`saml-core-test`, an SP) built on saml-core.
 */
abstract class TestCase extends PluginTestCase
{
    public const PLUGIN_HANDLE = 'saml-core-test';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraftCms\\Cms\\Database\\Factories\\'.class_basename($modelName).'Factory',
        );

        $this->plugin()->loadSaml2Container();
    }

    /**
     * saml-core isn't a plugin, so register KeyChain and the fixture plugin instead of the package.
     */
    #[Override]
    public function composerInstallPlugin(): void
    {
        $root = dirname(__DIR__);
        $keychain = json_decode((string) file_get_contents("$root/vendor/flipboxfactory/keychain/composer.json"), true);

        $this->app->when(Plugins::class)
            ->needs('$composerPlugins')
            ->give(fn () => [
                'flipboxfactory/keychain' => [...$keychain['extra'], 'basePath' => "$root/vendor/flipboxfactory/keychain/src"],
                'flipboxfactory/saml-core-test' => [
                    'class' => TestPlugin::class,
                    'handle' => self::PLUGIN_HANDLE,
                    'name' => 'SAML Core Test',
                    'version' => '1.0.0',
                    'schemaVersion' => '1.0.0',
                    'basePath' => __DIR__.'/fixtures/plugin/src',
                ],
            ]);
        $this->app->forgetInstance(Plugins::class);
    }

    #[Override]
    public function installAndEnablePlugin(): void
    {
        $plugins = $this->app->get(Plugins::class);
        $plugins->installPlugin(self::PLUGIN_HANDLE);
        $plugins->enablePlugin(self::PLUGIN_HANDLE);
        $plugins->enablePlugin('keychain');
        $this->app->forgetInstance(Plugins::class);
    }

    protected function plugin(): TestPlugin
    {
        /** @var TestPlugin */
        return TestPlugin::getInstance();
    }

    protected function admin(): User
    {
        return User::query()->where('admin', true)->firstOrFail();
    }
}
