<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 9:01 PM
 */

namespace flipbox\saml\core\traits;


use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use flipbox\saml\core\Saml;
use yii\base\Event;

trait SamlCore
{
    /**
     *
     */
    public function initCore()
    {
        /**
         * Register Core Module on Craft
         */
        if (! \Craft::$app->getModule(Saml::MODULE_ID)) {
            \Craft::$app->setModule(Saml::MODULE_ID, [
                'class' => Saml::class
            ]);
        }

        $this->registerTemplates();

    }

    /**
     * @return Saml
     */
    public function getCore(): Saml
    {
        return \Craft::$app->getModule(Saml::MODULE_ID);
    }

    /**
     *
     */
    protected function registerTemplates()
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $e) {
                if (is_dir($baseDir = Saml::getTemplateRoot())) {
                    $e->roots[Saml::getTemplateRootKey($this)] = $baseDir;
                }
            }
        );
    }
}