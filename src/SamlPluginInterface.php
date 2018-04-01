<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/8/18
 * Time: 9:56 PM
 */

namespace flipbox\saml\core;


use craft\base\Model;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\services\bindings\AbstractHttpPost;
use flipbox\saml\core\services\bindings\AbstractHttpRedirect;
use flipbox\saml\core\services\messages\MetadataServiceInterface;
use flipbox\saml\core\services\messages\SamlRequestInterface;
use flipbox\saml\core\services\messages\SamlResponseInterface;
use flipbox\saml\core\services\ProviderIdentityServiceInterface;
use flipbox\saml\core\services\ProviderServiceInterface;

interface SamlPluginInterface
{
    /**
     * @return ProviderServiceInterface
     */
    public function getProvider(): ProviderServiceInterface;

    public function getProviderIdentity(): ProviderIdentityServiceInterface;

    /**
     * @return MetadataServiceInterface
     */
    public function getMetadata(): MetadataServiceInterface;

    /**
     * @return SamlRequestInterface
     */
    public function getLogoutRequest(): SamlRequestInterface;

    /**
     * @return SamlResponseInterface
     */
    public function getLogoutResponse(): SamlResponseInterface;

    /**
     * @return SettingsInterface
     */
    public function getSettings(): SettingsInterface;

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
}