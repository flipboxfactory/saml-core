<?php

namespace flipbox\saml\core\tests\unit\controllers;

use Codeception\Test\Unit;
use Craft;
use craft\base\Model;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\AbstractSettingsController;

class AbstractSettingsControllerTest extends Unit
{
    private const HANDLE = 'saml-core-test';

    private const PATH = ProjectConfig::PATH_PLUGINS . '.' . self::HANDLE . '.settings';

    protected function _after(): void
    {
        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_PLUGINS . '.' . self::HANDLE);
    }

    private function storeSettings(array $settings): void
    {
        Craft::$app->getProjectConfig()->set(
            self::PATH,
            ProjectConfigHelper::packAssociativeArrays($settings)
        );
    }

    private function storedSettings(): array
    {
        return ProjectConfigHelper::unpackAssociativeArrays(
            Craft::$app->getProjectConfig()->get(self::PATH) ?? []
        );
    }

    public function testPostedValuesOverrideStoredValues(): void
    {
        $this->storeSettings(['entityId' => 'old', 'endpointPrefix' => 'old', 'enableUsers' => false]);

        $merged = AbstractSettingsController::mergeStoredSettings(self::HANDLE, [
            'entityId' => 'new',
            'endpointPrefix' => 'sso',
        ]);

        $this->assertEqualsCanonicalizing(
            ['entityId' => 'new', 'endpointPrefix' => 'sso', 'enableUsers' => false],
            $merged
        );
    }

    public function testNothingStoredReturnsPostedValues(): void
    {
        $posted = ['entityId' => 'new', 'endpointPrefix' => 'sso'];

        $this->assertSame($posted, AbstractSettingsController::mergeStoredSettings(self::HANDLE, $posted));
    }

    /**
     * Regression for flipboxfactory/saml-sp#262: saving the settings page dropped every
     * project config setting except the two posted ones (Craft 5.8.22+ / 4.16.18+).
     */
    public function testSavingPreservesOtherStoredSettings(): void
    {
        $stored = [
            'entityId' => 'https://sp.example.com',
            'endpointPrefix' => 'sso',
            'enableUsers' => false,
            'createUser' => false,
            'syncGroups' => false,
            'responseAttributeMap' => ['email' => 'mail', 'firstName' => 'givenName'],
        ];
        $this->storeSettings($stored);

        $plugin = $this->pluginWithSettings();
        $plugin->getSettings()->setAttributes($stored, false);

        $saved = Craft::$app->getPlugins()->savePluginSettings(
            $plugin,
            AbstractSettingsController::mergeStoredSettings(self::HANDLE, [
                'entityId' => 'https://sp.example.com/new',
                'endpointPrefix' => 'saml',
            ])
        );

        $this->assertTrue($saved);
        $expected = array_merge($stored, ['entityId' => 'https://sp.example.com/new', 'endpointPrefix' => 'saml']);
        $actual = $this->storedSettings();
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Values that only come from a config file must not be copied into project config.
     */
    public function testSavingDoesNotCopyConfigFileOnlyValues(): void
    {
        $this->storeSettings(['entityId' => 'https://sp.example.com', 'endpointPrefix' => 'sso']);

        $plugin = $this->pluginWithSettings();
        // Simulates config/<handle>.php overriding a setting that isn't in project config.
        $plugin->getSettings()->setAttributes(['enableUsers' => false], false);

        Craft::$app->getPlugins()->savePluginSettings(
            $plugin,
            AbstractSettingsController::mergeStoredSettings(self::HANDLE, [
                'entityId' => 'https://sp.example.com',
                'endpointPrefix' => 'sso',
            ])
        );

        $this->assertArrayNotHasKey('enableUsers', $this->storedSettings());
    }

    private function pluginWithSettings(): AbstractPlugin
    {
        $settings = new class() extends Model {
            public $entityId;
            public $endpointPrefix = 'sso';
            public $enableUsers = true;
            public $createUser = true;
            public $syncGroups = true;
            public $responseAttributeMap = [];
        };

        // Keep the real __get() so `$plugin->handle` resolves through getHandle() to the id.
        $plugin = $this->getMockBuilder(AbstractPlugin::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getSettings',
                'beforeSaveSettings',
                'afterSaveSettings',
                'loadSaml2Container',
                'getProviderRecordClass',
                'getProviderIdentityRecordClass',
            ])
            ->getMock();
        $plugin->id = self::HANDLE;
        $plugin->method('getSettings')->willReturn($settings);
        $plugin->method('beforeSaveSettings')->willReturn(true);

        return $plugin;
    }
}
