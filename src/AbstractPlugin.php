<?php
/**
 * @copyright Copyright (c) Flipbox Digital Limited
 * @license   https://flipboxfactory.com/software/saml-core/license
 * @link      https://www.flipboxfactory.com/software/saml-core/
 */

namespace flipbox\saml\core;

use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\StringHelper;
use flipbox\saml\core\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\services\bindings\Factory;
use flipbox\saml\core\services\Cp;
use flipbox\saml\core\services\EditProvider;
use flipbox\saml\core\services\messages\LogoutRequest;
use flipbox\saml\core\services\messages\LogoutResponse;
use flipbox\saml\core\services\Metadata;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;
use SAML2\Compat\AbstractContainer;
use yii\base\Event;

/**
 * Class AbstractPlugin
 *
 * @package flipbox\saml\core
 */
abstract class AbstractPlugin extends Plugin
{

    const SAML_CORE_HANDLE = 'saml-core';

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * @var bool
     */
    public bool $hasCpSection = true;

    abstract public function loadSaml2Container(): AbstractContainer;

    /**
     * @return class-string<ProviderInterface>
     */
    abstract public function getProviderRecordClass();

    /**
     * @return string
     */
    abstract public function getProviderIdentityRecordClass();

    public function init():void
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

        $this->setComponents(
            [
            'cp' => Cp::class,
            'metadata' => Metadata::class,
            'logoutRequest' => LogoutRequest::class,
            'logoutResponse' => LogoutResponse::class,
            'editProvider' => [
                'class' => EditProvider::class,
                'plugin' => $this,
            ],
            ]
        );

        /**
         * Set saml plugin to the craft variable
         */
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /**
            * @var CraftVariable $variable 
            */
                $variable = $event->sender;
                $variable->set($this->getPluginVariableHandle(), self::getInstance());
                $variable->set('samlCp', $this->getCp());
            }
        );

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $e) {
                if (is_dir($baseDir = (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates'))) {
                    $e->roots[static::SAML_CORE_HANDLE] = $baseDir;
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
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
        $nav = [
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
        ];

        if(\Craft::$app->config->general->allowAdminChanges) {
            $nav['saml.settings'] = [
                'url' => $this->getHandle() . '/settings',
                'label' => \Craft::t(
                    $this->getHandle(),
                    'Settings'
                ),
            ];
        }
        return $nav;
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        return array_merge(
            parent::getCpNavItem(), [
            'subnav' => $this->getSubNav(),
            ]
        );
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
                sprintf(
                    'POST,GET %s',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGIN_ENDPOINT
                    )
                ) => $handle . '/login',
                sprintf(
                    'POST,GET %s/<uid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGIN_ENDPOINT
                    )
                ) => $handle . '/login',
                sprintf(
                    'POST,GET %s',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGIN_REQUEST_ENDPOINT
                    )
                ) => $handle . '/login/request',
                sprintf(
                    'POST,GET %s'.
                    '/<externalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGIN_REQUEST_ENDPOINT
                    )
                ) => $handle . '/login/request',
                sprintf(
                    'POST,GET %s'.
                    '/<externalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>' .
                    '/<internalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGIN_REQUEST_ENDPOINT
                    )
                ) => $handle . '/login/request',
                /**
                 * LOGOUT
                 */
                sprintf(
                    'POST,GET %s/<uid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGOUT_ENDPOINT
                    )
                ) => $handle . '/logout',
                sprintf(
                    'POST,GET %s',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGOUT_ENDPOINT
                    )
                ) => $handle . '/logout',
                sprintf(
                    'POST,GET %s',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGOUT_REQUEST_ENDPOINT
                    )
                ) => $handle . '/logout/request',
                sprintf(
                    'POST,GET %s'.
                    '/<externalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGOUT_REQUEST_ENDPOINT
                    )
                ) => $handle . '/logout/request',
                sprintf(
                    'POST,GET %s'.
                    '/<externalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>'.
                    '/<internalUid:[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}>',
                    UrlHelper::buildEndpointPath(
                        static::getInstance()->getSettings(),
                        UrlHelper::LOGOUT_REQUEST_ENDPOINT
                    )
                ) => $handle . '/logout/request',

            ]
        );
    }

    /**
     * @return AbstractSettings
     */
    public function getSettings(): ?\craft\base\Model
    {
        return parent::getSettings();
    }

    /**
     * Components
     */

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return       Cp
     */
    public function getCp()
    {

        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        /**
 * @noinspection PhpIncompatibleReturnTypeInspection 
*/
        return $this->get('cp');
    }

    /**
     * @return EditProvider
     */
    public function getEditProvider()
    {

        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        return $this->get('editProvider');
    }

    /**
     * @return ProviderServiceInterface
     */
    public function getProvider()
    {

        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        return $this->get('provider');
    }

    /**
     * @return ProviderIdentityServiceInterface
     */
    public function getProviderIdentity()
    {
        return $this->get('providerIdentity');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return       Metadata
     */
    public function getMetadata()
    {
        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        /**
 * @noinspection PhpIncompatibleReturnTypeInspection 
*/
        return $this->get('metadata');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return       LogoutRequest
     * @throws       \yii\base\InvalidConfigException
     */
    public function getLogoutRequest()
    {
        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        /**
 * @noinspection PhpIncompatibleReturnTypeInspection 
*/
        return $this->get('logoutRequest');
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return       LogoutResponse
     * @throws       \yii\base\InvalidConfigException
     */
    public function getLogoutResponse()
    {
        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        /**
 * @noinspection PhpIncompatibleReturnTypeInspection 
*/
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
        /**
 * @noinspection PhpUnhandledExceptionInspection 
*/
        /**
 * @noinspection PhpIncompatibleReturnTypeInspection 
*/
        return $this->get('bindingFactory');
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
