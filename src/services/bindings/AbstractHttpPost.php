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
use flipbox\saml\core\exceptions\InvalidSignature;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\models\ProviderInterface;
use flipbox\saml\core\services\traits\Security;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Error\LightSamlBindingException;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\SamlMessage;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Class AbstractHttpPost
 * @package flipbox\saml\core\services\bindings
 */
abstract class AbstractHttpPost extends Component implements BindingInterface
{
    use Security;

    abstract function getTemplatePath();

    /**
     * @param SamlMessage $message
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function send(SamlMessage $message)
    {

        $parameters['RelayState'] = SerializeHelper::toBase64(Craft::$app->getUser()->getReturnUrl());
        $parameters[MessageHelper::getParameterKeyByMessage($message)] = SerializeHelper::base64Message(
            $message
        );

        $parameters['destination'] = $message->getDestination();
        $view = \Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        Craft::$app->request->data = $view->renderTemplate(
            $this->getTemplatePath(),
            $parameters
        );
        $view->setTemplateMode($templateMode);
        Craft::$app->response->sendAndClose();

    }

    /**
     * @param Request $request
     * @return \LightSaml\Model\Protocol\AuthnRequest|\LightSaml\Model\Protocol\LogoutRequest|\LightSaml\Model\Protocol\LogoutResponse|\LightSaml\Model\Protocol\Response|SamlMessage
     * @throws InvalidSignature
     * @throws \Exception
     */
    public function receive(Request $request)
    {

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
        $provider = $this->getProviderByIssuer($issuer);

        if(!$this->validSignature($message, $provider)){
            throw new InvalidSignature("Invalid request", 400);
        }

        if (array_key_exists('RelayState', $post)) {
            $message->setRelayState($post['RelayState']);
        }

        return $message;
    }

}