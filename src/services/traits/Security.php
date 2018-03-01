<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/16/18
 * Time: 9:59 AM
 */

namespace flipbox\saml\core\services\traits;


use flipbox\saml\core\models\ProviderInterface;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\X509Credential;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\EncryptedAssertionReader;
use LightSaml\Model\Assertion\EncryptedAssertionWriter;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use RobRichards\XMLSecLibs\XMLSecurityKey;

trait Security
{
    /**
     * @return X509Certificate
     */
    abstract public function getOurCertificateForThisProvider(ProviderInterface $provider, $keyDescriptor = KeyDescriptor::USE_SIGNING): X509Certificate;

    /**
     * @return XMLSecurityKey
     */
    abstract public function getOurKeyForThisProvider(ProviderInterface $provider, $keyDescriptor = KeyDescriptor::USE_SIGNING): XMLSecurityKey;

    /**
     * @return X509Certificate
     */
    public function getTheirCertificate(ProviderInterface $provider, $keyDescriptor = KeyDescriptor::USE_SIGNING): X509Certificate
    {
        return $provider->getMetadata()->getFirstSpSsoDescriptor()->getFirstKeyDescriptor($keyDescriptor)->getCertificate();
    }

    /**
     * @return XMLSecurityKey
     */
    public function getTheirKey(ProviderInterface $provider, $keyDescriptor = KeyDescriptor::USE_SIGNING): XMLSecurityKey
    {
        return KeyHelper::createPublicKey(
            $provider->getMetadata()->getFirstIdpSsoDescriptor()->getFirstKeyDescriptor($keyDescriptor)->getCertificate()
        );
    }

    /**
     * @param SamlMessage $message
     * @return SamlMessage
     */
    public function signMessage(SamlMessage $message, ProviderInterface $provider)
    {
        return $message->setSignature(new \LightSaml\Model\XmlDSig\SignatureWriter(
                $this->getOurCertificateForThisProvider($provider),
                $this->getOurKeyForThisProvider($provider)
            )
        );

    }

    /**
     * @param Response $response
     * @return Assertion[]
     */
    public function decryptAssertions(Response $response, ProviderInterface $provider)
    {
        $credential = new X509Credential(
            $this->getOurCertificateForThisProvider($provider),
            $this->getOurKeyForThisProvider($provider)
        );
        $readers = $response->getAllEncryptedAssertions();

        $decryptDeserializeContext = new \LightSaml\Model\Context\DeserializationContext();

        $assertions = [];
        foreach ($readers as $reader) {
            /** @var EncryptedAssertionReader $reader */
            $assertions[] = $reader->decryptMultiAssertion([$credential], $decryptDeserializeContext);
        }

        return $assertions;

    }

    /**
     * @param Assertion $assertion
     * @return EncryptedAssertionWriter
     */
    public function encryptAssertion(Assertion $assertion, ProviderInterface $provider)
    {
        $encryptedAssertion = new EncryptedAssertionWriter();
        $encryptedAssertion->encrypt($assertion,
            $this->getTheirKey($provider, KeyDescriptor::USE_ENCRYPTION)
        );

        return $encryptedAssertion;
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return bool
     */
    public function validSignature(SamlMessage $message, ProviderInterface $provider)
    {

        /** @var \LightSaml\Model\XmlDSig\SignatureXmlReader $signatureReader */
        $signatureReader = $message->getSignature();
        $key = $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING);
        try {

            if ($signatureReader->validate(
                KeyHelper::createPublicKey(
                    $key->getCertificate()
                )
            )) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }


    }
}