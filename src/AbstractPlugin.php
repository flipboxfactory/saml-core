<?php
/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/saml-core/license
 * @link       https://www.flipboxfactory.com/software/saml-core/
 */

namespace flipbox\saml\core;

use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use flipbox\craft\psr3\Logger;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\services\AbstractCp;
use flipbox\saml\core\services\AbstractProviderService;
use flipbox\saml\core\services\bindings\Factory;
use flipbox\saml\core\services\messages\AbstractLogoutRequest;
use flipbox\saml\core\services\messages\AbstractLogoutResponse;
use flipbox\saml\core\services\Metadata;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use SAML2\Compat\AbstractContainer;
use yii\base\Event;

/**
 * Class AbstractPlugin
 * @package flipbox\saml\core
 */
abstract class AbstractPlugin extends Plugin
{

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    abstract public function loadSaml2Container(): AbstractContainer;

    /**
     * @return string
     */
    abstract public function getProviderRecordClass();

    /**
     * @return string
     */
    abstract public function getProviderIdentityRecordClass();

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
        \Craft::setAlias('@flipbox/saml/core/web/assets', __DIR__ . '/web/assets');
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

        $this->setComponents([
            'psr3logger' => [
                'class' => Logger::class,
            ],
            'metadata' => Metadata::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {

        \Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl(static::getInstance()->getHandle() . '/settings')
        );

        \Craft::$app->end();
    }

    /**
     * @return array
     */
    private function getSubNav()
    {
        return [
            'saml.setup' => [
                'url' => $this->getHandle() . '/',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Setup'
                ),
            ],
            'saml.myProvider' => [
                'url' => $this->getHandle() . '/metadata/my-provider',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'My Provider'
                ),
            ],
            'saml.providers' => [
                'url' => $this->getHandle() . '/metadata',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Provider List'
                ),
            ],
            'saml.keychain' => [
                'url' => $this->getHandle() . '/keychain',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Keychain'
                ),
            ],
            'saml.settings' => [
                'url' => $this->getHandle() . '/settings',
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
            'subnav' => $this->getSubNav(),
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
                if (is_dir($baseDir = $this->getTemplateRoot())) {
                    $e->roots[$this->getTemplateRootKey()] = $baseDir;
                }
            }
        );
    }

    /**
     * @return string
     */
    public function getMyType()
    {
        return $this->getSettings()->getMyType();
    }

    /**
     * @return string
     */
    public function getRemoteType()
    {
        $type = SettingsInterface::SP;
        if ($this->getMyType() === SettingsInterface::SP) {
            $type = SettingsInterface::IDP;
        }

        return $type;
    }

    /**
     * @return string
     */
    public function getTemplateRoot()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates';
    }

    /**
     * @return string
     */
    public function getTemplateRootKey()
    {
        return $this->getHandle() . '-' . 'core';
    }
    /**
     * EVENTs
     */

    /**
     * @param RegisterUrlRulesEvent $event
     */
    public static function onRegisterCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $handle = static::getInstance()->getHandle();
        $event->rules = array_merge(
            $event->rules,
            [
                $handle . '/' => $handle . '/cp/view/general/setup',
                $handle . '/settings' => $handle . '/cp/view/general/settings',

                /**
                 * Keychain
                 */
                $handle . '/keychain' => $handle . '/cp/view/keychain/index',
                $handle . '/keychain/new' => $handle . '/cp/view/keychain/edit',
                $handle . '/keychain/new-openssl' => $handle . '/cp/view/keychain/edit/openssl',
                $handle . '/keychain/<keypairId:\d+>' => $handle . '/cp/view/keychain/edit',

                /**
                 * Metadata
                 */
                $handle . '/metadata' => $handle . '/cp/view/metadata/default',
                $handle . '/metadata/new' => $handle . '/cp/view/metadata/edit',
                $handle . '/metadata/new-idp' => $handle . '/cp/view/metadata/edit/new-idp',
                $handle . '/metadata/new-sp' => $handle . '/cp/view/metadata/edit/new-sp',
                $handle . '/metadata/my-provider' => $handle . '/cp/view/metadata/edit/my-provider',
                $handle . '/metadata/<providerId:\d+>' => $handle . '/cp/view/metadata/edit',
            ]
        );
    }

    /**
     * @param RegisterUrlRulesEvent $event
     */
    public static function onRegisterSiteUrlRules(RegisterUrlRulesEvent $event)
    {
        $handle = static::getInstance()->getHandle();
        $event->rules = array_merge(
            $event->rules,
            [
                /**
                 * LOGIN
                 */
                'POST,GET /sso/login' => $handle . '/login',
                sprintf(
                    'GET %s',
                    (string)static::getInstance()->getSettings()->loginRequestEndpoint
                ) => $handle . '/login/request',
                sprintf(
                    'GET %s/<uid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    (string)static::getInstance()->getSettings()->loginRequestEndpoint
                ) => $handle . '/login/request',
                /**
                 * LOGOUT
                 */
                'POST,GET /sso/logout' => $handle . '/logout',
                sprintf(
                    'GET %s',
                    (string)static::getInstance()->getSettings()->logoutRequestEndpoint
                ) => $handle . '/logout/request',
            ]
        );
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
     * @returns AbstractProviderService
     */
    public function getProvider()
    {

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
     * @return Metadata
     */
    public function getMetadata()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('metadata');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return AbstractLogoutRequest
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
     * @return AbstractLogoutResponse
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
     * @return Factory
     * @throws \yii\base\InvalidConfigException
     */
    public function getBindingFactory()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('bindingFactory');
    }

    /**
     * @return Logger
     * @throws \yii\base\InvalidConfigException
     */
    public function getPsr3Logger()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->get('psr3logger');
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
