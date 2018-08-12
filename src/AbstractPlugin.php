<?php
/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/saml-core/license
 * @link       https://www.flipboxfactory.com/software/saml-core/
 */

namespace flipbox\saml\core;


use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use flipbox\saml\core\services\AbstractCp;
use flipbox\saml\core\services\bindings\AbstractHttpPost;
use flipbox\saml\core\services\bindings\AbstractHttpRedirect;
use flipbox\saml\core\services\messages\MetadataServiceInterface;
use flipbox\saml\core\services\messages\SamlRequestInterface;
use flipbox\saml\core\services\messages\SamlResponseInterface;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;
use yii\base\Event;
use craft\helpers\StringHelper;

/**
 * Class AbstractPlugin
 * @package flipbox\saml\core
 */
abstract class AbstractPlugin extends Plugin
{

    /**
     * Saml Constants
     */
    const SP = 'sp';
    const IDP = 'idp';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = true;

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
        \Craft::setAlias('@flipbox/saml/core', __DIR__);

        /**
         * Register Core Module on Craft
         */
        if (! \Craft::$app->getModule(Saml::MODULE_ID)) {
            \Craft::$app->setModule(Saml::MODULE_ID, [
                'class' => Saml::class
            ]);
        }

        $this->registerTemplates();

        /**
         * Set saml plugin to the craft variable
         */
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set($this->getPluginVariableHandle(), self::getInstance());
            }
        );
    }

    /**
     * @return array
     */
    private function getSubNav()
    {
        return [
            'saml.setup'      => [
                'url'   => $this->getHandle() . '/',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Setup'
                ),
            ],
            'saml.myProvider' => [
                'url'   => $this->getHandle() . '/metadata/my-provider',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'My Provider'
                ),
            ],
            'saml.providers'  => [
                'url'   => $this->getHandle() . '/metadata',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Providers'
                ),
            ],
            'saml.keychain'   => [
                'url'   => $this->getHandle() . '/keychain',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Keychain'
                ),
            ],
            'saml.settings'   => [
                'url'   => $this->getHandle() . '/settings',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Settings'
                ),
            ],
        ];

    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        return array_merge(parent::getCpNavItem(), [
            'subnav' => $this->getSubNav()
        ]);
    }

    /**
     * @return string
     */
    public function getPluginVariableHandle()
    {
        return StringHelper::camelCase($this->handle);
    }

    /**
     * Registering the core templates for SP and IDP to use.
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
     * @noinspection PhpDocMissingThrowsInspection
     * @returns AbstractCp
     */
    public function getCp()
    {

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('cp');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @returns ProviderServiceInterface
     */
    public function getProvider()
    {

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('provider');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @returns ProviderIdentityServiceInterface
     */
    public function getProviderIdentity()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('providerIdentity');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return MetadataServiceInterface
     */
    public function getMetadata()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('metadata');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return SamlRequestInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutRequest()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('logoutRequest');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return SamlResponseInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutResponse()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('logoutResponse');
    }

    /**
     * Bindings
     */

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return AbstractHttpPost
     */
    public function getHttpPost()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('httpPost');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return AbstractHttpRedirect
     */
    public function getHttpRedirect()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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