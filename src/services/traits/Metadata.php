<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/25/18
 * Time: 3:32 PM
 */

namespace flipbox\saml\core\services\traits;
use flipbox\saml\core\SamlPluginInterface;
use LightSaml\Model\Metadata\SSODescriptor;
use flipbox\keychain\records\KeyChainRecord;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Credential\X509Certificate;


trait Metadata
{

    /**
     * @return string
     */
    abstract public function getLogoutResponseLocation();

    /**
     * @return string
     */
    abstract public function getLogoutRequestLocation();

    /**
     * @return string
     */
    abstract public function getLoginLocation();

    abstract protected function getSamlPlugin(): SamlPluginInterface;

    public function setSign(SSODescriptor $spSsoDescriptor, KeyChainRecord $keyChainRecord)
    {
        if ($this->getSamlPlugin()->getSettings()->signAssertions) {

            $spSsoDescriptor->addKeyDescriptor(
                $keyDescriptor = (new KeyDescriptor())
                    ->setUse(KeyDescriptor::USE_SIGNING)
                    ->setCertificate((new X509Certificate())->loadPem($keyChainRecord->certificate))
            );
        }

    }

    public function setEncrypt(SSODescriptor $spSsoDescriptor, KeyChainRecord $keyChainRecord)
    {

        if ($this->getSamlPlugin()->getSettings()->encryptAssertions) {
            $spSsoDescriptor->addKeyDescriptor(
                $keyDescriptor = (new KeyDescriptor())
                    ->setUse(KeyDescriptor::USE_ENCRYPTION)
                    ->setCertificate((new X509Certificate())->loadPem($keyChainRecord->certificate))
            );

        }
    }

}