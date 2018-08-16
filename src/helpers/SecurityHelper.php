<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/14/18
 * Time: 9:40 PM
 */

namespace flipbox\saml\core\helpers;


use flipbox\keychain\records\KeyChainRecord;
use LightSaml\Credential\KeyHelper;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Credential\X509Certificate;
use LightSaml\Credential\KeyHelper as LightSamlKeyHelper;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

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

        $privateKey = LightSamlKeyHelper::createPrivateKey(
            $pair->getDecryptedKey(),
            '',
            false,
            XMLSecurityKey::RSA_SHA256
        );

        $message->setSignature(new SignatureWriter($cert, $privateKey, XMLSecurityDSig::SHA256));
    }


    /**
     * @param SamlMessage $message
     * @param KeyDescriptor $keyDescriptor
     * @return bool
     */
    public static function validSignature(SamlMessage $message, KeyDescriptor $keyDescriptor)
    {

        /** @var \LightSaml\Model\XmlDSig\SignatureXmlReader $signatureReader */
        $signatureReader = $message->getSignature();

        try {

            if (static::validate(
                $signatureReader,
                KeyHelper::createPublicKey(
                    $keyDescriptor->getCertificate()
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

    /**
     * @param KeyChainRecord $chainRecord
     * @return \LightSaml\Credential\X509Credential
     */
    public static function createCredential(KeyChainRecord $chainRecord)
    {
        $credential = new X509Certificate();
        $credential->loadPem($chainRecord->getDecryptedCertificate());

        return new \LightSaml\Credential\X509Credential(
            $credential,
            \LightSaml\Credential\KeyHelper::createPrivateKey(
                $chainRecord->getDecryptedKey(),
                '',
                false,
                XMLSecurityKey::RSA_SHA256
            )
        );
    }

    /**
     * @param SignatureXmlReader $reader
     * @param XMLSecurityKey $key
     * @return bool
     * @throws \Exception
     */
    public static function validate(SignatureXmlReader $reader, XMLSecurityKey $key)
    {
        if (null == $reader->getSignature()) {
            return false;
        }

        if (false == $reader->getSignature()->validateReference()) {
            throw new LightSamlSecurityException('Digest validation failed');
        }

        $key = static::castKeyIfNecessary($key, $reader);

        /**
         * @see \RobRichards\XMLSecLibs\XMLSecurityDSig::verify
         * NOTE: be very careful when checking the int return value, because in
         * PHP, -1 will be cast to True when in boolean context. Always check the
         * return value in a strictly typed way, e.g. "$obj->verify(...) === 1".
         */
        if (1 !== $reader->getSignature()->verify($key)) {
            throw new LightSamlSecurityException('Unable to verify Signature');
        }

        return true;
    }

    /**
     * @param XMLSecurityKey $key
     *
     * @return XMLSecurityKey
     */
    protected static function castKeyIfNecessary(XMLSecurityKey $key, SignatureXmlReader $reader)
    {
        $algorithm = $reader->getAlgorithm();

        if (! in_array($algorithm, [
            XMLSecurityKey::RSA_SHA1,
            XMLSecurityKey::RSA_SHA256,
            XMLSecurityKey::RSA_SHA384,
            XMLSecurityKey::RSA_SHA512,
        ])) {
            throw new LightSamlSecurityException(sprintf('Unsupported signing algorithm: "%s"', $algorithm));
        }

        if ($algorithm != $key->type) {
            $key = KeyHelper::castKey($key, $algorithm);
        }

        return $key;
    }
}