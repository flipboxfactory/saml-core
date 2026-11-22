<?php

declare(strict_types=1);

namespace flipbox\saml\core;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Http\Middleware\RequireAdmin;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use flipbox\saml\core\containers\Logger;
use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
use flipbox\saml\core\services\AbstractProviderIdentityService;
use flipbox\saml\core\services\AbstractProviderService;
use flipbox\saml\core\services\Cp;
use flipbox\saml\core\services\EditProvider;
use flipbox\saml\core\services\messages\LogoutRequest;
use flipbox\saml\core\services\messages\LogoutResponse;
use flipbox\saml\core\services\Metadata;
use flipbox\saml\core\services\Session;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use LogicException;
use SAML2\Compat\AbstractContainer;

use function CraftCms\Cms\t;

/**
 * Base for the SAML plugins (saml-sp and saml-idp).
 *
 * Subclasses provide their records, services ({@see services()}) and controllers ({@see controllers()}). This class
 * registers the SAML endpoints and control panel routes for them, so every SAML plugin serves the same URLs:
 *
 * - `{endpointPrefix}/login[/{uid}]`, `{endpointPrefix}/logout[/{uid}]` (from partners; GET or POST, no CSRF)
 * - `{endpointPrefix}/login/request[/{externalUid}[/{internalUid}]]` and the same for `logout/request` (start SSO/SLO)
 * - control panel screens under `{handle}/…`, and form actions under `actions/{handle}/…`, for admins only
 *
 * @method AbstractSettings getSettings()
 */
abstract class AbstractPlugin extends Plugin
{
    public const SAML_CORE_HANDLE = 'saml-core';

    private const UUID = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = true;

    /**
     * @var array<string, object>
     */
    private array $serviceInstances = [];

    /**
     * Creates and registers this plugin's saml2 container, e.g. `new Saml2Container($this)` set on saml2's
     * `ContainerSingleton`.
     */
    abstract public function loadSaml2Container(): AbstractContainer;

    /**
     * @return class-string<AbstractProvider>
     */
    abstract public function getProviderRecordClass(): string;

    /**
     * @return class-string<AbstractProviderIdentity>
     */
    abstract public function getProviderIdentityRecordClass(): string;

    /**
     * Controller classes for the routes this class registers. Required keys:
     *
     * - `login`: `index` (`{prefix}/login[/{uid}]`) and `request` (`{prefix}/login/request/…`)
     * - `logout`: an {@see controllers\messages\AbstractLogoutController}
     * - `metadata`: an {@see controllers\AbstractMetadataController}
     * - `settings`: an {@see controllers\AbstractSettingsController}
     * - `cp.general`, `cp.metadata.default`, `cp.metadata.edit`: control panel screens
     * - `cp.keychain.index`, `cp.keychain.edit`: keychain's screens for this plugin
     *
     * @return array<string, class-string>
     */
    abstract protected function controllers(): array;

    /**
     * This plugin's services, by ID. Subclasses add their own (at least `provider` and `providerIdentity`) and can
     * replace core ones. Each class is constructed once per plugin, with the plugin as its `$plugin` argument.
     *
     * @return array<string, class-string>
     */
    protected function services(): array
    {
        return [
            'cp' => Cp::class,
            'metadata' => Metadata::class,
            'logoutRequest' => LogoutRequest::class,
            'logoutResponse' => LogoutResponse::class,
            'editProvider' => EditProvider::class,
            'session' => Session::class,
        ];
    }

    /**
     * One of this plugin's services.
     */
    public function service(string $id): object
    {
        if (isset($this->serviceInstances[$id])) {
            return $this->serviceInstances[$id];
        }

        $class = $this->services()[$id] ?? throw new LogicException("Plugin [$this->handle] has no [$id] service.");

        return $this->serviceInstances[$id] = $this->app->make($class, ['plugin' => $this]);
    }

    public function boot(): void
    {
        // `craft.samlSp` / `craft.samlIdp` in templates, as in 5.x
        $plugin = $this;
        CraftVariable::macro($this->getPluginVariableHandle(), fn () => $plugin);

        // saml-core's own templates (e.g. the HTTP-POST binding form), as `saml-core/…`
        $this->app->make(TemplateRoots::class)->register(TemplateMode::Cp, self::SAML_CORE_HANDLE, __DIR__.'/templates');
    }

    public function bootHasRoutes(): void
    {
        parent::bootHasRoutes();

        if ($this->app->routesAreCached()) {
            return;
        }

        $this->registerEndpointRoutes();
        $this->registerControlPanelRoutes();
        $this->registerFormActionRoutes();
    }

    /**
     * The SAML endpoints. Partners POST to these from their own sites, so CSRF protection doesn't apply; the
     * messages are authenticated by their signatures instead.
     */
    protected function registerEndpointRoutes(): void
    {
        $controllers = $this->requireControllers(['login', 'logout']);
        $login = UrlHelper::buildEndpointPath($this->getSettings(), UrlHelper::LOGIN_ENDPOINT);
        $logout = UrlHelper::buildEndpointPath($this->getSettings(), UrlHelper::LOGOUT_ENDPOINT);
        $name = "saml.$this->handle.";

        $this->registerWebRoutes(function () use ($controllers, $login, $logout, $name) {
            Route::withoutMiddleware(PreventRequestForgery::class)->group(function () use ($controllers, $login, $logout, $name) {
                foreach (['login' => $login, 'logout' => $logout] as $type => $path) {
                    Route::match(['GET', 'POST'], "$path/request/{externalUid?}/{internalUid?}", [$controllers[$type], 'request'])
                        ->where(['externalUid' => self::UUID, 'internalUid' => self::UUID])
                        ->name("$name$type.request");
                    Route::match(['GET', 'POST'], "$path/{uid?}", [$controllers[$type], 'index'])
                        ->where('uid', self::UUID)
                        ->name("$name$type");
                }
            });
        });
    }

    protected function registerControlPanelRoutes(): void
    {
        $c = $this->requireControllers(['cp.general', 'cp.metadata.default', 'cp.metadata.edit', 'cp.keychain.index', 'cp.keychain.edit']);
        $handle = $this->handle;

        $this->registerCpRoutes(function () use ($c, $handle) {
            Route::middleware(RequireAdmin::class)->name("saml.$handle.cp.")->group(function () use ($c, $handle) {
                Route::get($handle, [$c['cp.general'], 'setup'])->name('setup');
                Route::get("$handle/settings", [$c['cp.general'], 'settings'])->name('settings');
                Route::get("$handle/metadata", [$c['cp.metadata.default'], 'index'])->name('metadata');
                Route::get("$handle/metadata/new", [$c['cp.metadata.edit'], 'index'])->name('metadata.new');
                Route::get("$handle/metadata/new-idp", [$c['cp.metadata.edit'], 'newIdp'])->name('metadata.new-idp');
                Route::get("$handle/metadata/new-sp", [$c['cp.metadata.edit'], 'newSp'])->name('metadata.new-sp');
                Route::get("$handle/metadata/my-provider", [$c['cp.metadata.edit'], 'myProvider'])->name('metadata.my-provider');
                Route::get("$handle/metadata/{providerId}", [$c['cp.metadata.edit'], 'index'])->whereNumber('providerId')->name('metadata.edit');
                Route::get("$handle/keychain", [$c['cp.keychain.index'], 'index'])->name('keychain');
                Route::get("$handle/keychain/new", [$c['cp.keychain.edit'], 'index'])->name('keychain.new');
                Route::get("$handle/keychain/new-openssl", [$c['cp.keychain.edit'], 'openssl'])->name('keychain.openssl');
                Route::get("$handle/keychain/{keypairId}", [$c['cp.keychain.edit'], 'index'])->whereNumber('keypairId')->name('keychain.edit');
            });
        });
    }

    /**
     * Form actions at `actions/{handle}/…`, the Craft 5 action paths. Craft also registers them on the site's
     * action trigger without authentication, so they require an admin here; Craft's web middleware applies CSRF.
     */
    protected function registerFormActionRoutes(): void
    {
        $c = $this->requireControllers(['metadata', 'settings']);

        $this->registerActionRoutes(function () use ($c) {
            Route::middleware(RequireAdmin::class)->group(function () use ($c) {
                Route::get('metadata', [$c['metadata'], 'index']);
                Route::get('metadata/download-certificate', [$c['metadata'], 'downloadCertificate']);
                Route::post('metadata/save', [$c['metadata'], 'save']);
                Route::post('metadata/auto-create', [$c['metadata'], 'autoCreate']);
                Route::post('metadata/change-status', [$c['metadata'], 'changeStatus']);
                Route::match(['post', 'delete'], 'metadata/delete', [$c['metadata'], 'delete']);
                Route::post('settings/save', [$c['settings'], 'save']);
            });
        });
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, class-string>
     */
    private function requireControllers(array $keys): array
    {
        $controllers = $this->controllers();
        $missing = array_diff($keys, array_keys($controllers));

        if ($missing !== []) {
            throw new LogicException(sprintf('Plugin [%s] must provide controllers for: %s.', $this->handle, implode(', ', $missing)));
        }

        return $controllers;
    }

    public function getSettingsResponse(): mixed
    {
        return redirect()->to(\CraftCms\Cms\Support\Url::cpUrl($this->handle.'/settings'));
    }

    public function getCpNavItem(): NavItem|array|null
    {
        $item = parent::getCpNavItem();

        if ($item instanceof NavItem) {
            $item->subnav(array_map(
                fn (string $id, array $link) => new NavItem()->id($id)->label($link['label'])->href(\CraftCms\Cms\Support\Url::cpUrl($link['url'])),
                array_keys($this->getSubNav()),
                $this->getSubNav(),
            ));
        }

        return $item;
    }

    /**
     * @return array<string, array{url: string, label: string}>
     */
    protected function getSubNav(): array
    {
        $nav = [
            'saml.setup' => ['url' => $this->handle, 'label' => t('Setup', category: $this->handle)],
            'saml.myProvider' => ['url' => $this->handle.'/metadata/my-provider', 'label' => t('My Provider', category: $this->handle)],
            'saml.providers' => ['url' => $this->handle.'/metadata', 'label' => t('Provider List', category: $this->handle)],
            'saml.keychain' => ['url' => $this->handle.'/keychain', 'label' => t('Keychain', category: $this->handle)],
        ];

        if (\CraftCms\Cms\Cms::config()->allowAdminChanges) {
            $nav['saml.settings'] = ['url' => $this->handle.'/settings', 'label' => t('Settings', category: $this->handle)];
        }

        return $nav;
    }

    public function getPluginVariableHandle(): string
    {
        return Str::camel($this->handle);
    }

    public function getMyType(): ?string
    {
        return $this->getSettings()->getMyType();
    }

    public function getRemoteType(): string
    {
        return $this->getMyType() === SettingsInterface::SP ? SettingsInterface::IDP : SettingsInterface::SP;
    }

    public function getCp(): Cp
    {
        /** @var Cp */
        return $this->service('cp');
    }

    public function getEditProvider(): EditProvider
    {
        /** @var EditProvider */
        return $this->service('editProvider');
    }

    public function getProvider(): AbstractProviderService
    {
        /** @var AbstractProviderService */
        return $this->service('provider');
    }

    public function getProviderIdentity(): AbstractProviderIdentityService
    {
        /** @var AbstractProviderIdentityService */
        return $this->service('providerIdentity');
    }

    public function getMetadata(): Metadata
    {
        /** @var Metadata */
        return $this->service('metadata');
    }

    public function getLogoutRequest(): LogoutRequest
    {
        /** @var LogoutRequest */
        return $this->service('logoutRequest');
    }

    public function getLogoutResponse(): LogoutResponse
    {
        /** @var LogoutResponse */
        return $this->service('logoutResponse');
    }

    public function getSession(): Session
    {
        /** @var Session */
        return $this->service('session');
    }

    public function getLogger(): Logger
    {
        return new Logger($this->handle);
    }

    public static function error(string $message): void
    {
        Log::error($message, ['category' => static::getInstance()->handle]);
    }

    public static function warning(string $message): void
    {
        Log::warning($message, ['category' => static::getInstance()->handle]);
    }

    public static function info(string $message): void
    {
        Log::info($message, ['category' => static::getInstance()->handle]);
    }

    public static function debug(string $message): void
    {
        Log::debug($message, ['category' => static::getInstance()->handle]);
    }
}
