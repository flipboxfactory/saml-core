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
use flipbox\saml\core\services\bindings\AbstractHttpPost;
use flipbox\saml\core\services\bindings\AbstractHttpRedirect;
use flipbox\saml\core\services\messages\MetadataServiceInterface;
use flipbox\saml\core\services\messages\SamlRequestInterface;
use flipbox\saml\core\services\messages\SamlResponseInterface;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;
use yii\base\Event;

abstract class AbstractPlugin extends Plugin
{

    /**
     * Saml Constants
     */
    const SP = 'sp';
    const IDP = 'idp';


    /**
     * @return string
     */
    abstract public function getMyType();

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
     * @return Saml|null
     */
    public function getCore()
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
    public function getRemoteType()
    {
        $type = static::SP;
        if ($this->getMyType() === static::SP) {
            $type = static::IDP;
        }

        return $type;
    }

    /**
     * Components
     */

    /**
     * @returns ProviderServiceInterface
     */
    public function getProvider()
    {
        return $this->get('provider');
    }

    /**
     * @returns ProviderIdentityServiceInterface
     */
    public function getProviderIdentity()
    {
        return $this->get('providerIdentity');
    }

    /**
     * @return MetadataServiceInterface
     */
    public function getMetadata()
    {
        return $this->get('metadata');
    }

    /**
     * @return SamlRequestInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutRequest()
    {
        return $this->get('logoutRequest');
    }

    /**
     * @return SamlResponseInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutResponse()
    {
        return $this->get('logoutResponse');
    }

    /**
     * Bindings
     */

    /**
     * @return AbstractHttpPost
     */
    public function getHttpPost()
    {
        return $this->get('httpPost');
    }

    /**
     * @return AbstractHttpRedirect
     */
    public function getHttpRedirect()
    {
        return $this->get('httpRedirect');
    }


    /**
     * Log Functions
     */

    /**
     * @param $message
     */
    public static function error($message)
    {
        \Craft::error($message, static::getInstance()->getHandle());
    }
    /**
     * @param $message
     */
    public static function warning($message)
    {
        \Craft::warning($message, static::getInstance()->getHandle());
    }
    /**
     * @param $message
     */
    public static function info($message)
    {
        \Craft::info($message, static::getInstance()->getHandle());
    }
    /**
     * @param $message
     */
    public static function debug($message)
    {
        \Craft::debug($message, static::getInstance()->getHandle());
    }
}