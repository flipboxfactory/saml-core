<?php

declare(strict_types=1);

namespace flipbox\saml\core\helpers;

use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\AbstractProvider;

/**
 * Builds the SAML endpoint paths and URLs. These URLs are in partners' metadata, so they must stay as they are:
 * `{site base URL}/{endpointPrefix}/{endpoint}/{provider UID}`.
 */
class UrlHelper
{
    public const LOGIN_ENDPOINT = 'login';

    public const LOGOUT_ENDPOINT = 'logout';

    public const LOGIN_REQUEST_ENDPOINT = 'login/request';

    public const LOGOUT_REQUEST_ENDPOINT = 'logout/request';

    /**
     * The route path of an endpoint, e.g. `sso/login`.
     */
    public static function buildEndpointPath(AbstractSettings $settings, string $endpoint): string
    {
        return implode('/', [$settings->getEndpointPrefix(), $endpoint]);
    }

    /**
     * The URL of an endpoint for a provider, on the provider's site when it has one with URLs.
     */
    public static function buildEndpointUrl(
        AbstractSettings $settings,
        string $endpoint,
        AbstractProvider $provider,
        bool $fullUrl = true,
    ): string {
        $uri = implode('/', [$settings->getEndpointPrefix(), $endpoint, $provider->uid]);

        if (! $fullUrl) {
            return '/'.$uri;
        }

        $site = $provider->site;
        $baseUrl = $site !== null && $site->hasUrls ? (string) $site->baseUrl : Url::baseSiteUrl();

        return implode('/', [static::providerBaseUrl($baseUrl), $uri]);
    }

    /**
     * Resolves a site base URL that may be an alias (`@web`) or environment variable (`$PRIMARY_SITE_URL`), without
     * its trailing slash. Anything else that isn't an absolute URL resolves to an empty string, as in 5.x.
     */
    protected static function providerBaseUrl(string $baseUrl): string
    {
        $url = '';
        if (str_starts_with($baseUrl, '@') || str_starts_with($baseUrl, '$')) {
            $url = (string) Env::parse($baseUrl);
        } elseif (str_starts_with($baseUrl, 'http')) {
            $url = $baseUrl;
        }

        return (string) preg_replace('#/$#', '', $url);
    }
}
