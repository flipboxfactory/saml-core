<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/11/18
 * Time: 9:44 PM
 */

namespace flipbox\saml\core\services\bindings;


use craft\base\Component;
use craft\web\Request;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use LightSaml\Error\LightSamlBindingException;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\SignatureStringReader;
use LightSaml\SamlConstants;
use flipbox\saml\core\helpers\SerializeHelper;

abstract class AbstractHttpRedirect extends Component implements BindingInterface
{

    /**
     * @param SamlMessage $message
     * @param AbstractProvider $provider
     */
    public function send(SamlMessage $message, ProviderInterface $provider)
    {

        if ($signature = $message->getSignature()) {
            $message->setSignature(null);
            SerializeHelper::addSignatureToUrl($parameters, $signature);
            $destination = SerializeHelper::redirectUrl($message->getDestination(), $parameters);
        } else {
            $destination = SerializeHelper::redirectUrl($message->getDestination(), $parameters);
        }

        \Craft::$app->response->redirect($destination);

    }

    /**
     * @param Request $request
     * @return \LightSaml\Model\Protocol\AuthnRequest|\LightSaml\Model\Protocol\LogoutRequest|\LightSaml\Model\Protocol\LogoutResponse|\LightSaml\Model\Protocol\Response|SamlMessage
     * @throws \Exception
     */
    public function receive(Request $request)
    {
        $data = $this->parseQuery($request);
        $encodedMessage = $this->getMessage($data);
        $encoding = $this->getEncoding($data);
        $messageString = $this->decodeMessageString($encodedMessage, $encoding);


        $message = SamlMessage::fromXML($messageString, new DeserializationContext());

        if ($request->getQueryParam('RelayState')) {
            $message->setRelayState((string)$request->getQueryParam('RelayState'));
        }

        $queryData = $this->getSignedQuery($data);
        $this->loadSignature($message, $queryData);

        return $message;

    }

    protected function parseQuery(Request $request)
    {
        /*
         * Parse the query string. We need to do this ourself, so that we get access
         * to the raw (urlencoded) values. This is required because different software
         * can urlencode to different values.
         */
        $sigQuery = $relayState = $sigAlg = '';
        $data = $this->parseQueryString($request->getQueryString(), false);
        $result = array();
        foreach ($data as $name => $value) {
            $result[$name] = urldecode($value);
            switch ($name) {
                case 'SAMLRequest':
                case 'SAMLResponse':
                    $sigQuery = $name . '=' . $value;
                    break;
                case 'RelayState':
                    $relayState = '&RelayState=' . $value;
                    break;
                case 'SigAlg':
                    $sigAlg = '&SigAlg=' . $value;
                    break;
            }
        }
        $result['SignedQuery'] = $sigQuery . $relayState . $sigAlg;

        return $result;
    }

    /**
     * @param string $queryString
     * @param bool $urlDecodeValues
     *
     * @return array
     */
    protected function parseQueryString($queryString, $urlDecodeValues = false)
    {
        $result = array();
        foreach (explode('&', $queryString) as $e) {
            $tmp = explode('=', $e, 2);
            $name = $tmp[0];
            $value = count($tmp) === 2 ? $value = $tmp[1] : '';
            $name = urldecode($name);
            $result[$name] = $urlDecodeValues ? urldecode($value) : $value;
        }

        return $result;
    }

    /**
     * @param SamlMessage $message
     * @param array $data
     */
    protected function loadSignature(SamlMessage $message, array $data)
    {
        if (array_key_exists('Signature', $data)) {
            if (false == array_key_exists('SigAlg', $data)) {
                throw new LightSamlBindingException('Missing signature algorithm');
            }
            $message->setSignature(
                new SignatureStringReader($data['Signature'], urldecode($data['SigAlg']), $data['SignedQuery'])
            );
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getSignedQuery(array $data)
    {
        $sigQuery = $relayState = $sigAlg = '';
        foreach ($data as $name => $value) {
            switch ($name) {
                case 'SAMLRequest':
                case 'SAMLResponse':
                    $sigQuery = $name . '=' . $value;
                    break;
                case 'RelayState':
                    $relayState = '&RelayState=' . $value;
                    break;
                case 'SigAlg':
                    $sigAlg = '&SigAlg=' . urldecode($value);
                    break;
            }
        }
        $data['SignedQuery'] = $sigQuery . $relayState . $sigAlg;
        return $data;

    }


    /**
     * @param array $data
     * @return mixed
     */
    protected function getMessage(array $data)
    {
        if (array_key_exists('SAMLRequest', $data)) {
            return $data['SAMLRequest'];
        } elseif (array_key_exists('SAMLResponse', $data)) {
            return $data['SAMLResponse'];
        } else {
            throw new LightSamlBindingException('Missing SAMLRequest or SAMLResponse parameter');
        }
    }

    /**
     * @param array $data
     * @return mixed|string
     */
    protected function getEncoding(array $data)
    {
        if (array_key_exists('SAMLEncoding', $data)) {
            return $data['SAMLEncoding'];
        } else {
            return SamlConstants::ENCODING_DEFLATE;
        }
    }

    /**
     * @param $msg
     * @param $encoding
     * @return string
     */
    protected function decodeMessageString($msg, $encoding)
    {
        $msg = base64_decode($msg);
        switch ($encoding) {
            case SamlConstants::ENCODING_DEFLATE:
                return gzinflate($msg);
                break;
            default:
                throw new LightSamlBindingException(sprintf("Unknown encoding '%s'", $encoding));
        }
    }
}