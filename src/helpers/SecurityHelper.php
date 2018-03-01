<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/14/18
 * Time: 9:40 PM
 */

namespace flipbox\saml\core\helpers;


use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\ProviderInterface;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\KeyHelper as LightSamlKeyHelper;
use LightSaml\Model\XmlDSig\SignatureWriter;

class SecurityHelper
{

    /**
     * @param SamlMessage $message
     * @param KeyChainRecord $pair
     */
    public static function signMessage(SamlMessage $message, KeyChainRecord $pair)
    {
        $cert = new X509Certificate();
        $cert->loadPem($pair->getDecryptedCertificate());

        $privateKey = LightSamlKeyHelper::createPrivateKey($pair->getDecryptedKey(), '');

        $message->setSignature(new SignatureWriter($cert, $privateKey));
    }


    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return bool
     */
    public static function validSignature(SamlMessage $message, KeyDescriptor $keyDescriptor)
    {

        /** @var \LightSaml\Model\XmlDSig\SignatureXmlReader $signatureReader */
        $signatureReader = $message->getSignature();
//        try {

            if ($signatureReader->validate(
                KeyHelper::createPublicKey(
                    $keyDescriptor->getCertificate()
                )
            )) {
                return true;
            } else {
                return false;
            }
//        } catch (\Exception $e) {
//            return false;
//        }


    }
}