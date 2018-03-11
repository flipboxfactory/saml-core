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
use flipbox\saml\core\services\ProviderServiceInterface;

interface SamlPluginInterface
{
    /**
     * @return KeyChain
     */
    public function getKeyChain(): KeyChain;

    /**
     * @return ProviderServiceInterface
     */
    public function getProvider(): ProviderServiceInterface;

    /**
     * @return MetadataServiceInterface
     */
    public function getMetadata(): MetadataServiceInterface;

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
}