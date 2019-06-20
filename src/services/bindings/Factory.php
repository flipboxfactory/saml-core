<?php

namespace flipbox\saml\core\services\bindings;

use craft\base\Component;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use SAML2\Constants;
use SAML2\AuthnRequest;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\Message as SamlMessage;
use SAML2\Response;

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
        $binding = static::determineSendBinding($message, $provider);

        $binding->send($message);
    }

    /**
     * @param SamlMessage $message
     * @param AbstractProvider $provider
     * @return HTTPPost|HTTPRedirect
     */
    protected static function determineSendBinding(SamlMessage $message, AbstractProvider $provider)
    {
        $binding = null;
        switch (true) {
            case ($message instanceof AuthnRequest):
                $binding = $provider->firstIdpSsoService(Constants::BINDING_HTTP_POST)
                    ??
                    $provider->firstIdpSsoService();
                break;
            case ($message instanceof Response):
                $binding = $provider->firstSpAcsService(Constants::BINDING_HTTP_POST)
                    ??
                    $provider->firstSpAcsService();
                break;
            case ($message instanceof LogoutRequest):
            case ($message instanceof LogoutResponse):
                $binding = static::getSLOEndpoint($provider);
                break;
        }
        return $binding->getBinding() === Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
    }

    /**
     * @param SamlMessage $message
     * @param AbstractProvider $provider
     * @return \SAML2\XML\md\IndexedEndpointType|null
     */
    protected static function getSLOEndpoint(AbstractProvider $provider)
    {
        return $provider->getType() === $provider::TYPE_IDP ? (
            $provider->firstIdpSloService(Constants::BINDING_HTTP_POST)
            ??
            $provider->firstIdpSloService()
        ) : (
            $provider->firstSpSloService(Constants::BINDING_HTTP_POST)
            ??
            $provider->firstSpSloService()
        );
    }
}
