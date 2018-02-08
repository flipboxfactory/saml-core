<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/9/18
 * Time: 12:25 PM
 */

namespace flipbox\saml\core\helpers;

use craft\web\Response;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\AbstractSamlModel;
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


    /**
     * @param array $parameters
     * @param SignatureWriter|null $signature
     * @return array
     * @todo move to AbstractHttpRedirect ... maybe?
     */
    public static function addSignatureToUrl(array $parameters, SignatureWriter $signature = null)
    {
        /** @var $key XMLSecurityKey */
        $key = $signature ? $signature->getXmlSecurityKey() : null;

        if (null != $key) {
            $parameters['SigAlg'] = urlencode($key->type);
            $signature = $key->signData(http_build_query($parameters));
            $parameters['Signature'] = base64_encode($signature);
        }

        return $parameters;

    }
}