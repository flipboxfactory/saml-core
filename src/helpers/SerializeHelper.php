<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/9/18
 * Time: 12:25 PM
 */

namespace flipbox\saml\core\helpers;

use craft\web\Response;
use flipbox\saml\core\models\Transport;
use LightSaml\Error\LightSamlBindingException;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\AbstractSamlModel;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\SignatureWriter;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SerializeHelper
{
    /**
     * @param SamlMessage $message
     * @param bool $deflate
     * @return string
     */
    public static function base64Message(SamlMessage $message, $deflate = false)
    {

        $xml = static::toXml($message);
        if($deflate){
            $xml = gzdeflate($xml);
        }

        return base64_encode($xml);

    }

    /**
     * @param $parameter
     * @return string
     */
    public static function toBase64($parameter)
    {
        return base64_encode($parameter);
    }

    /**
     * @param AbstractSamlModel $model
     * @return string
     */
    public static function toXml(AbstractSamlModel $message)
    {
        $context = new SerializationContext(new \DOMDocument('1.0', 'UTF-8'));
        $message->serialize($context->getDocument(), $context);
        return $context->getDocument()->saveXML();
    }

    /**
     * set proper headers to present xml correctly
     */
    public static function xmlContentType()
    {
        \Craft::$app->getResponse()->format = Response::FORMAT_RAW;
        \Craft::$app->getResponse()->getHeaders()->add('Content-Type', 'text/xml');
    }

    /**
     * @param string $location
     * @param array $parameters
     * @return string
     */
    public static function redirectUrl(string $location, array $parameters)
    {

        return $location .
            (strpos($location, '?') === false ? '?' : '&') .
            http_build_query($parameters);
    }


    public static function getRedirectURL(SamlMessage $message, $destination)
    {
        $signature = $message->getSignature();
        if ($signature && false == $signature instanceof SignatureWriter) {
            throw new LightSamlBindingException('Signature must be SignatureWriter');
        }

        $message->setSignature(null);
        $xml = static::toXml($message);
        $xml = gzdeflate($xml);
        $xml = base64_encode($xml);
        $msg = static::addMessageToUrl($message, $xml);
        static::addRelayStateToUrl($msg, $message);
        static::addSignatureToUrl($msg, $signature);

        return static::getDestinationUrl($msg, $message, $destination);
    }

    /**
     * @param SamlMessage $message
     * @param string      $xml
     *
     * @return string
     */
    protected static function addMessageToUrl(SamlMessage $message, $xml)
    {
        if ($message instanceof AbstractRequest) {
            $msg = 'SAMLRequest=';
        } else {
            $msg = 'SAMLResponse=';
        }
        $msg .= urlencode($xml);

        return $msg;
    }

    /**
     * @param string      $msg
     * @param SamlMessage $message
     */
    protected static function addRelayStateToUrl(&$msg, SamlMessage $message)
    {
        if ($message->getRelayState() !== null) {
            $msg .= '&RelayState='.urlencode($message->getRelayState());
        }
    }

    /**
     * @param string               $msg
     * @param SignatureWriter|null $signature
     */
    protected static function addSignatureToUrl(&$msg, SignatureWriter $signature = null)
    {
        /** @var $key XMLSecurityKey */
        $key = $signature ? $signature->getXmlSecurityKey() : null;

        if (null != $key) {
            $msg .= '&SigAlg='.urlencode($key->type);
            $signature = $key->signData($msg);
            $msg .= '&Signature='.urlencode(base64_encode($signature));
        }
    }

    /**
     * @param string      $msg
     * @param SamlMessage $message
     * @param string|null $destination
     *
     * @return string
     */
    protected static function getDestinationUrl($msg, SamlMessage $message, $destination)
    {
        $destination = $message->getDestination() ? $message->getDestination() : $destination;
        if (strpos($destination, '?') === false) {
            $destination .= '?'.$msg;
        } else {
            $destination .= '&'.$msg;
        }

        return $destination;
    }
}