<?php

namespace flipbox\saml\core\services\bindings;

use craft\base\Component;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use SAML2\Constants;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\Message as SamlMessage;

/**
 * Class Factory
 * @package flipbox\saml\core\services\bindings
 */
class Factory extends Component
{
    /**
     * @return SamlMessage
     * @throws \Exception
     */
    public static function receive()
    {
        $request = \Craft::$app->request;
        switch ($request->getMethod()) {
            case 'POST':
                $binding = new HTTPPost;
                break;
            case 'GET':
            default:
                $binding = new HTTPRedirect;
        }

        return $binding->receive();

    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return mixed
     * @throws InvalidMetadata
     */
    public static function send(SamlMessage $message, AbstractProvider $provider)
    {
        if ($provider->getType() === $provider::TYPE_IDP) {
            $binding = static::determineBindingFromIdp($message, $provider);
        } else {
            $binding = static::determineBindingFromSp($message, $provider);
        }

        $binding->send($message);
    }

    /**
     * @param SamlMessage $message
     * @param AbstractProvider $provider
     * @return HTTPPost|HTTPRedirect
     */
    public static function determineBindingFromSp(SamlMessage $message, AbstractProvider $provider)
    {
        if (MessageHelper::isRequest($message)) {

            // Get POST by default
            $endpoint = $provider->firstSpAcsService(
                    Constants::BINDING_HTTP_POST
                ) ?? $provider->firstSpAcsService(
                    Constants::BINDING_HTTP_REDIRECT
                );
            $binding = $endpoint->getBinding() == Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
        } else {

            // Get POST by default
            $endpoint = $provider->firstSpSloService(
                    Constants::BINDING_HTTP_POST
                ) ?? $provider->firstSpSloService(
                    Constants::BINDING_HTTP_REDIRECT
                );
            $binding = $endpoint->getBinding() == Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
        }


        return $binding;
    }

    /**
     * @param SamlMessage $message
     * @param AbstractProvider $provider
     * @return HTTPPost|HTTPRedirect
     */
    public static function determineBindingFromIdp(SamlMessage $message, AbstractProvider $provider)
    {

        if (MessageHelper::isRequest($message)) {

            // Get POST by default
            $endpoint = $provider->firstIdpSsoService(
                    Constants::BINDING_HTTP_POST
                ) ?? $provider->firstIdpSsoService(
                    Constants::BINDING_HTTP_REDIRECT
                );
            $binding = $endpoint->getBinding() == Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
        } else {

            // Get POST by default
            $endpoint = $provider->firstSpSloService(
                    Constants::BINDING_HTTP_POST
                ) ?? $provider->firstSpSloService(
                    Constants::BINDING_HTTP_REDIRECT
                );
            $binding = $endpoint->getBinding() == Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
        }

        return $binding;
    }

}
