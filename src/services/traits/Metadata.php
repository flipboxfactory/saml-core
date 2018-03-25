<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/25/18
 * Time: 3:32 PM
 */

namespace flipbox\saml\core\services\traits;

use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\SamlPluginInterface;
use LightSaml\Model\Metadata\SSODescriptor;
use flipbox\keychain\records\KeyChainRecord;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Credential\X509Certificate;
use LightSaml\SamlConstants;


trait Metadata
{
    /**
     * @var array
     */
    protected $supportedBindings = [
        SamlConstants::BINDING_SAML2_HTTP_POST,
    ];

    /**
     * @return array
     */
    abstract public function getSupportedBindings();

    /**
     * @return SamlPluginInterface
     */
    abstract protected function getSamlPlugin(): SamlPluginInterface;

    /**
     * @param AbstractProvider $provider
     * @return bool
     */
    abstract protected function useSigning(AbstractProvider $provider);

    /**
     * @param AbstractProvider $provider
     * @return bool
     */
    abstract protected function useEncryption(AbstractProvider $provider);

    protected function supportsRedirect()
    {
        return in_array(SamlConstants::BINDING_SAML2_HTTP_REDIRECT, $this->getSupportedBindings());
    }

    protected function supportsPost()
    {
        return in_array(SamlConstants::BINDING_SAML2_HTTP_POST, $this->getSupportedBindings());
    }

    /**
     * @param AbstractProvider $record
     * @return AbstractProvider
     * @throws \Exception
     */
    public function saveProvider(AbstractProvider $record)
    {
        return $this->getSamlPlugin()->getProvider()->save($record);
    }

    /**
     * @param SSODescriptor $ssoDescriptor
     * @param KeyChainRecord $keyChainRecord
     */
    public function setSign(SSODescriptor $ssoDescriptor, KeyChainRecord $keyChainRecord)
    {
        $ssoDescriptor->addKeyDescriptor(
            $keyDescriptor = (new KeyDescriptor())
                ->setUse(KeyDescriptor::USE_SIGNING)
                ->setCertificate((new X509Certificate())->loadPem($keyChainRecord->getDecryptedCertificate()))
        );
    }

    /**
     * @param SSODescriptor $ssoDescriptor
     * @param KeyChainRecord $keyChainRecord
     */
    public function setEncrypt(SSODescriptor $ssoDescriptor, KeyChainRecord $keyChainRecord)
    {
        $ssoDescriptor->addKeyDescriptor(
            $keyDescriptor = (new KeyDescriptor())
                ->setUse(KeyDescriptor::USE_ENCRYPTION)
                ->setCertificate((new X509Certificate())->loadPem($keyChainRecord->getDecryptedCertificate()))
        );
    }

}