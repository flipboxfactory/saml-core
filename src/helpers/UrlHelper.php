<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/15/18
 * Time: 10:35 PM
 */

namespace flipbox\saml\core\helpers;

use craft\helpers\UrlHelper as BaseUrlHelper;

class UrlHelper extends BaseUrlHelper
{

    /**
     * @inheritdoc
     */
    public static function actionUrl(string $path = '', $params = null, string $scheme = null): string
    {
        $path = \Craft::$app->getConfig()->getGeneral()->actionTrigger . '/' . trim($path, '/');

        return static::url($path, $params, $scheme, false);
    }

}