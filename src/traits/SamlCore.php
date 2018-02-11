<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 9:01 PM
 */

namespace flipbox\saml\core\traits;


use flipbox\saml\core\Saml;

trait SamlCore
{
    public function initCore()
    {
        /**
         * Register Core Module on Craft
         */
        if(!\Craft::$app->getModule(Saml::MODULE_ID)) {
            \Craft::$app->setModule(Saml::MODULE_ID, [
                'class' => Saml::class
            ]);
        }
    }

    /**
     * @return Saml
     */
    public function getCore(): Saml
    {
        return \Craft::$app->getModule(Saml::MODULE_ID);
    }

}