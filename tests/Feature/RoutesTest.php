<?php

declare(strict_types=1);

use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

function routeFor(string $method, string $uri): Illuminate\Routing\Route
{
    return Route::getRoutes()->match(Illuminate\Http\Request::create($uri, $method));
}

it('builds endpoint URLs as 5.x did', function () {
    $provider = new ProviderRecord(['uid' => '0b0b0b0b-1111-2222-3333-444444444444', 'providerType' => 'idp']);
    $settings = test()->plugin()->getSettings();

    expect(UrlHelper::buildEndpointPath($settings, UrlHelper::LOGIN_ENDPOINT))->toBe('sso/login')
        ->and($provider->getLoginRequestPath())->toBe('/sso/login/request/0b0b0b0b-1111-2222-3333-444444444444')
        ->and($provider->getLogoutRequestPath())->toBe('/sso/logout/request/0b0b0b0b-1111-2222-3333-444444444444')
        ->and($provider->getLoginEndpoint())->toBe('https://localhost/sso/login/0b0b0b0b-1111-2222-3333-444444444444')
        ->and($provider->getLogoutEndpoint())->toBe('https://localhost/sso/logout/0b0b0b0b-1111-2222-3333-444444444444');
});

it('serves the SAML endpoints at the 5.x URLs', function (string $uri, string $action) {
    foreach (['GET', 'POST'] as $method) {
        expect(routeFor($method, $uri)->getActionMethod())->toBe($action);
    }
})->with([
    ['/sso/login', 'index'],
    ['/sso/login/0b0b0b0b-1111-2222-3333-444444444444', 'index'],
    ['/sso/login/request', 'request'],
    ['/sso/login/request/0b0b0b0b-1111-2222-3333-444444444444', 'request'],
    ['/sso/login/request/0b0b0b0b-1111-2222-3333-444444444444/0b0b0b0b-1111-2222-3333-555555555555', 'request'],
    ['/sso/logout', 'index'],
    ['/sso/logout/0b0b0b0b-1111-2222-3333-444444444444', 'index'],
    ['/sso/logout/request', 'request'],
    ['/sso/logout/request/0b0b0b0b-1111-2222-3333-444444444444', 'request'],
]);

it('only matches UIDs in endpoint paths', function () {
    expect(routeFor('GET', '/sso/login/not-a-uid')->getControllerClass())
        ->not->toBe(flipbox\saml\core\tests\fixtures\plugin\controllers\LoginController::class);
});

it('exempts only the SAML endpoints from CSRF protection', function () {
    $middleware = fn (string $method, string $uri) => Route::gatherRouteMiddleware(routeFor($method, $uri));

    expect($middleware('POST', '/sso/login'))->not->toContain(PreventRequestForgery::class)
        ->and(Route::getRoutes()->match(Illuminate\Http\Request::create('/sso/login', 'POST'))->excludedMiddleware())->toContain(PreventRequestForgery::class)
        ->and(routeFor('POST', '/admin/actions/saml-core-test/metadata/save')->excludedMiddleware())->not->toContain(PreventRequestForgery::class);
});

it('requires an admin for control panel screens and actions', function (string $method, string $uri) {
    // Solo only allows one user
    \CraftCms\Cms\Edition::set(\CraftCms\Cms\Edition::Pro);
    $this->actingAs(\CraftCms\Cms\User\Models\User::factory()->withPermissions(['accessCp'])->create(['admin' => false]), 'craft');

    $this->json($method, $uri)->assertForbidden();
})->with([
    ['GET', '/admin/saml-core-test'],
    ['GET', '/admin/saml-core-test/metadata'],
    ['GET', '/admin/saml-core-test/metadata/1'],
    ['GET', '/admin/saml-core-test/keychain'],
    ['POST', '/admin/actions/saml-core-test/metadata/save'],
    ['POST', '/actions/saml-core-test/metadata/save'],
    ['POST', '/admin/actions/saml-core-test/settings/save'],
]);

it('exposes the plugin to templates as craft.samlCoreTest', function () {
    expect(CraftCms\Cms\renderString('{{ craft.samlCoreTest.handle }}'))->toBe('saml-core-test');
});
