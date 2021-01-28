<?php


namespace flipbox\saml\core\helpers;

use craft\helpers\UrlHelper as CraftUrlHelper;
use craft\records\Site;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\AbstractProvider;

class UrlHelper extends CraftUrlHelper
{
    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    const LOGIN_ENDPOINT = 'login';

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    const LOGOUT_ENDPOINT = 'logout';

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    const LOGIN_REQUEST_ENDPOINT = 'login/request';

    /**
     * This is the endpoint used to initiate logout. In this case, `logoutPath` cannot be used.
     * Point your logout button to this endpoint.
     *
     * @var string
     */
    const LOGOUT_REQUEST_ENDPOINT = 'logout/request';

    /**
     * @param AbstractSettings $settings
     * @param string $endpoint
     * @return string
     */
    public static function buildEndpointPath(AbstractSettings $settings, string $endpoint)
    {
        return implode('/', [$settings->getEndpointPrefix(), $endpoint,]);
    }

    /**
     * @param string $baseUrl
     * @return string
     */
    protected static function providerBaseUrl(string $baseUrl)
    {
        $url = '';
        // alias
        if (strpos($baseUrl, '@') === 0) {
            $url = \Craft::getAlias($baseUrl);
        }
        // env var
        if (strpos($baseUrl, '$') === 0) {
            $url = \Craft::parseEnv($baseUrl);
        }
        // url
        if (strpos($baseUrl, 'http') === 0) {
            $url = $baseUrl;
        }

        // Trim last /
        return preg_replace('#/$#', '', $url);
    }

    /**
     * @param AbstractSettings $settings
     * @param string $endpoint
     * @param AbstractProvider $provider
     * @param bool $fullUrl
     * @return string
     * @throws \yii\base\Exception
     */
    public static function buildEndpointUrl(AbstractSettings $settings, string $endpoint, AbstractProvider $provider, $fullUrl = true)
    {
        $uri = implode('/', [$settings->getEndpointPrefix(), $endpoint, $provider->uid]);
        /** @var Site $site */
        $site = $provider->site;



        $endpointUrl = $fullUrl ?  implode('/', [
            static::providerBaseUrl(
                $site ? (string)$site->baseUrl : UrlHelper::baseUrl()
            ),
            $uri
        ]) : "/" . $uri;

        return $endpointUrl;
    }
}
