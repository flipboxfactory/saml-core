<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 9:01 PM
 */

namespace flipbox\saml\core;


use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;
use flipbox\saml\sp\Saml as SamlSp;

abstract class AbstractPlugin extends Plugin
{

    /**
     * Saml Constants
     */
    const SP = 'sp';
    const IDP = 'idp';

    public function init()
    {
        parent::init();

        $this->initCore();
    }

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

    /**
     * @return string
     */
    public function getMyType()
    {
        $type = static::IDP;
        if ($this instanceof SamlSp) {
            $type = static::SP;
        }

        return $type;
    }

    public function getRemoteType()
    {
        $type = static::SP;
        if ($this->getMyType() === static::SP) {
            $type = static::IDP;
        }

        return $type;
    }
}