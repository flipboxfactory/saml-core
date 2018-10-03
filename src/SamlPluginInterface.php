<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/8/18
 * Time: 9:56 PM
 */

namespace flipbox\saml\core;

use craft\base\PluginInterface;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\services\AbstractCp;
use flipbox\saml\core\services\bindings\AbstractHttpPost;
use flipbox\saml\core\services\bindings\AbstractHttpRedirect;
use flipbox\saml\core\services\messages\MetadataServiceInterface;
use flipbox\saml\core\services\messages\SamlRequestInterface;
use flipbox\saml\core\services\messages\SamlResponseInterface;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;
use flipbox\saml\core\services\Session;

/**
 * Interface SamlPluginInterface
 * @package flipbox\saml\core
 * @property string $name
 */
interface SamlPluginInterface extends PluginInterface
{
    /**
     * @return ProviderServiceInterface
     */
    public function getProviderRecordClass();

    /**
     * @return ProviderIdentityServiceInterface
     */
    public function getProviderIdentityRecordClass();

    /**
     * @return string
     */
    public function getTemplateRootKey();

    /**
     * @return string
     */
    public function getTemplateRoot();

    /**
     * @return ProviderServiceInterface
     */
    public function getProvider();

    /**
     * @return ProviderIdentityServiceInterface
     */
    public function getProviderIdentity();

    /**
     * @return MetadataServiceInterface
     */
    public function getMetadata();

    /**
     * @return SamlRequestInterface
     */
    public function getLogoutRequest();

    /**
     * @return SamlResponseInterface
     */
    public function getLogoutResponse();

    /**
     * @return SettingsInterface
     */
    public function getSettings();

    /**
     * @return AbstractCp
     */
    public function getCp();

    /**
     * @return string
     */
    public function getPluginVariableHandle();

    /**
     * BINDINGs
     */

    /**
     * @return AbstractHttpPost
     */
    public function getHttpPost();

    /**
     * @return AbstractHttpRedirect
     */
    public function getHttpRedirect();

    /**
     * Utility Methods
     */

    /**
     * @return string
     */
    public function getMyType();

    /**
     * @return string
     */
    public function getRemoteType();

    /**
     * @return Session
     */
    public function getSession();
}
