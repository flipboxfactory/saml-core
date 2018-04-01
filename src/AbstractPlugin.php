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
use flipbox\saml\core\services\messages\AbstractLogoutRequest;
use flipbox\saml\core\services\messages\AbstractLogoutResponse;
use flipbox\saml\core\services\messages\MetadataServiceInterface;
use flipbox\saml\core\services\messages\SamlRequestInterface;
use flipbox\saml\core\services\messages\SamlResponseInterface;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;
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
    public function getProvider(): ProviderServiceInterface
    {
        return $this->get('provider');
    }

    /**
     * @returns ProviderIdentityServiceInterface
     */
    public function getProviderIdentity(): ProviderIdentityServiceInterface
    {
        return $this->get('providerIdentity');
    }

    /**
     * @return MetadataServiceInterface
     */
    public function getMetadata(): MetadataServiceInterface
    {
        return $this->get('metadata');
    }

    /**
     * @return SamlRequestInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutRequest(): SamlRequestInterface
    {
        return $this->get('logoutRequest');
    }

    /**
     * @return SamlResponseInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getLogoutResponse(): SamlResponseInterface
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
}