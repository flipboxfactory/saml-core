<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/11/18
 * Time: 9:44 PM
 */

namespace flipbox\saml\core\services\bindings;

use Craft;
use craft\base\Component;
use craft\web\Request;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\exceptions\InvalidIssuer;
use flipbox\saml\core\exceptions\InvalidSignature;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Error\LightSamlBindingException;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\SamlMessage;

/**
 * Class AbstractHttpPost
 * @package flipbox\saml\core\services\bindings
 */
abstract class AbstractHttpPost extends Component implements BindingInterface
{
    use EnsureSamlPlugin;

    abstract public function getTemplatePath();

    /**
     * @inheritdoc
     */
    public function getProviderByIssuer(Issuer $issuer): ProviderInterface
    {
        $provider = $this->getSamlPlugin()->getProvider()->findByEntityId(
            $issuer->getValue()
        )->one();
        if (! $provider) {
            throw new InvalidIssuer(
                sprintf("Invalid issuer: %s", $issuer->getValue())
            );
        }

        /** @var ProviderInterface $provider */
        return $provider;
    }

    /**
     * @param SamlMessage $message
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function send(SamlMessage $message, ProviderInterface $provider)
    {

        $parameters = [];
        $parameters['destination'] = $message->getDestination();
        $parameters[MessageHelper::getParameterKeyByMessage($message)] = SerializeHelper::base64Message(
            $message
        );

        $parameters['RelayState'] = $this->getRelayStateForSend($message);

        $this->post($parameters);
    }

    /**
     * @param array $parameters
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\ExitException
     */
    public function post(array $parameters)
    {

        $view = \Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        Craft::$app->response->data = $view->renderTemplate(
            $this->getTemplatePath(),
            $parameters
        );
        Craft::$app->response->send();
        Craft::$app->end();
    }


    /**
     * @param SamlMessage $message
     * @return null|string
     */
    public function getRelayStateForSend(SamlMessage $message)
    {
        /**
         * It may already be there so grab and go
         */
        $relayState = $message->getRelayState();

        \Craft::info('[saml-core]' . $relayState);

        /**
         * Use craft to get it
         */
        if (! $relayState && MessageHelper::isRequest($message)) {
            /**
             * base64 encode it
             */
            $relayState = SerializeHelper::toBase64(
                Craft::$app->getUser()->getReturnUrl()
            );
        }

        return $relayState;
    }

    /**
     * @param Request $request
     * @return \LightSaml\Model\Protocol\AuthnRequest|\LightSaml\Model\Protocol\LogoutRequest|\LightSaml\Model\Protocol\LogoutResponse|\LightSaml\Model\Protocol\Response|SamlMessage
     * @throws InvalidSignature
     * @throws \Exception
     */
    public function receive(Request $request)
    {

        $ownProvider = $this->getSamlPlugin()->getProvider()->findOwn();

        $post = $request->getBodyParams();

        if (array_key_exists('SAMLRequest', $post)) {
            $msg = $post['SAMLRequest'];
        } elseif (array_key_exists('SAMLResponse', $post)) {
            $msg = $post['SAMLResponse'];
        } else {
            throw new LightSamlBindingException('Missing SAMLRequest or SAMLResponse parameter');
        }

        $msg = base64_decode($msg);

        $context = new MessageContext();
        $deserializationContext = $context->getDeserializationContext();
        $message = SamlMessage::fromXML($msg, $deserializationContext);

        /** @var Issuer $issuer */
        $issuer = $message->getIssuer();

        /** @var ProviderInterface $provider */
        $provider = $this->getProviderByIssuer($issuer);

        /**
         * Find the first key descriptor
         */
        if ($provider->providerType === AbstractPlugin::IDP
        ) {
            $key = $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstKeyDescriptor(
                \LightSaml\Model\Metadata\KeyDescriptor::USE_SIGNING
            );
        } else {
            $key = $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstKeyDescriptor(
                \LightSaml\Model\Metadata\KeyDescriptor::USE_SIGNING
            );
        }


        /**
         * Validate Signature
         */
        if ($message->getSignature() && ! SecurityHelper::validSignature($message, $key)) {
            throw new InvalidSignature("Invalid request", 400);
        }

        /**
         * Set Relay State
         */
        if (array_key_exists('RelayState', $post)) {
            $message->setRelayState($post['RelayState']);
        }

        return $message;
    }
}
