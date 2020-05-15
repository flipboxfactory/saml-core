<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use craft\web\Response;

class SerializeHelper
{

    /**
     * set proper headers to present xml correctly
     */
    public static function xmlContentType()
    {
        \Craft::$app->getResponse()->format = Response::FORMAT_RAW;
        \Craft::$app->getResponse()->getHeaders()->add('Content-Type', 'text/xml');
    }

    /**
     * @param string $location
     * @param array $parameters
     * @return string
     */
    public static function redirectUrl(string $location, array $parameters)
    {

        return $location .
            (strpos($location, '?') === false ? '?' : '&') .
            http_build_query($parameters);
    }
}
