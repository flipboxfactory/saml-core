<?php

namespace flipbox\saml\core\tests\unit\controllers;

use Craft;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use flipbox\saml\core\controllers\AbstractSettingsController;
use PHPUnit\Framework\TestCase;

/**
 * Regression for flipboxfactory/saml-sp#262: since Craft 4.16.18, savePluginSettings() only
 * writes the keys it is given, so saving the settings page dropped every other stored setting.
 */
class AbstractSettingsControllerTest extends TestCase
{
    private const HANDLE = 'saml-core-test';

    /** @var mixed */
    private $originalApp;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
    }

    protected function tearDown(): void
    {
        Craft::$app = $this->originalApp;
    }

    /**
     * @param mixed $stored What project config holds at plugins.<handle>.settings
     */
    private function useStoredSettings($stored): void
    {
        $projectConfig = $this->createMock(ProjectConfig::class);
        $projectConfig->method('get')
            ->with(ProjectConfig::PATH_PLUGINS . '.' . self::HANDLE . '.settings')
            ->willReturn($stored);

        Craft::$app = new class($projectConfig) {
            private $projectConfig;

            public function __construct($projectConfig)
            {
                $this->projectConfig = $projectConfig;
            }

            public function getProjectConfig()
            {
                return $this->projectConfig;
            }
        };
    }

    public function testKeepsOtherStoredSettingsAndOverridesPostedOnes(): void
    {
        $this->useStoredSettings(ProjectConfigHelper::packAssociativeArrays([
            'entityId' => 'https://sp.example.com',
            'endpointPrefix' => 'sso',
            'enableUsers' => false,
            'createUser' => false,
            'syncGroups' => false,
            'responseAttributeMap' => ['email' => 'mail', 'firstName' => 'givenName'],
        ]));

        $merged = AbstractSettingsController::mergeStoredSettings(self::HANDLE, [
            'entityId' => 'https://sp.example.com/new',
            'endpointPrefix' => 'saml',
        ]);

        $this->assertSame([
            'entityId' => 'https://sp.example.com/new',
            'endpointPrefix' => 'saml',
            'enableUsers' => false,
            'createUser' => false,
            'syncGroups' => false,
            // Unpacked, so savePluginSettings() doesn't pack it twice.
            'responseAttributeMap' => ['email' => 'mail', 'firstName' => 'givenName'],
        ], $merged);
    }

    public function testNothingStoredReturnsPostedValues(): void
    {
        $this->useStoredSettings(null);

        $posted = ['entityId' => 'https://sp.example.com', 'endpointPrefix' => 'sso'];

        $this->assertSame($posted, AbstractSettingsController::mergeStoredSettings(self::HANDLE, $posted));
    }
}
