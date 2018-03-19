<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/22/18
 * Time: 11:55 AM
 */

namespace flipbox\saml\core;

use craft\base\Plugin;
use yii\base\Module;


class Saml extends Module
{

    const MODULE_ID = 'saml-core';

    public function init()
    {
        \Craft::setAlias('@modules', __DIR__);

        /**
         * Don't know why I have to do this but I do for craft cli to work.
         */
        \Craft::setAlias('@flipbox/saml/core/controllers', __DIR__ . '/controllers');
        parent::init();

    }

    /**
     * @return string
     */
    public static function getTemplateRoot()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates';
    }

    /**
     * @param Plugin $saml
     * @return string
     */
    public static function getTemplateRootKey(Plugin $saml)
    {
        return $saml->getHandle() . '-' . 'core';
    }
}
