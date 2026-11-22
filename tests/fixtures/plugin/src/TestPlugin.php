<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\containers\Saml2Container;
use SAML2\Compat\AbstractContainer;
use SAML2\Compat\ContainerSingleton;

/**
 * A minimal SAML service provider plugin built on saml-core, for saml-core's tests.
 */
class TestPlugin extends AbstractPlugin
{
    public string $schemaVersion = '1.0.0';

    public function loadSaml2Container(): AbstractContainer
    {
        $container = new Saml2Container($this);
        ContainerSingleton::setContainer($container);

        return $container;
    }

    public function getProviderRecordClass(): string
    {
        return ProviderRecord::class;
    }

    public function getProviderIdentityRecordClass(): string
    {
        return ProviderIdentityRecord::class;
    }

    protected function services(): array
    {
        return [
            ...parent::services(),
            'provider' => ProviderService::class,
            'providerIdentity' => ProviderIdentityService::class,
        ];
    }

    protected function controllers(): array
    {
        return [
            'login' => controllers\LoginController::class,
            'logout' => controllers\LogoutController::class,
            'metadata' => controllers\MetadataController::class,
            'settings' => controllers\SettingsController::class,
            'cp.general' => controllers\GeneralController::class,
            'cp.metadata.default' => controllers\MetadataListController::class,
            'cp.metadata.edit' => controllers\MetadataEditController::class,
            'cp.keychain.index' => controllers\KeychainIndexController::class,
            'cp.keychain.edit' => controllers\KeychainEditController::class,
        ];
    }

    protected static function createSettings(): Settings
    {
        return new Settings;
    }
}
