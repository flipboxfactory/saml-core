<?php

declare(strict_types=1);

namespace flipbox\saml\core\services\bindings;

use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use SAML2\AuthnRequest;
use SAML2\Constants;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\Message as SamlMessage;
use SAML2\Response;
use SAML2\XML\md\EndpointType;

/**
 * Receives and sends SAML messages with the HTTP-POST and HTTP-Redirect bindings.
 *
 * Receiving only parses a message. Callers must verify its signature (and everything else) before trusting it.
 */
class Factory
{
    /**
     * Parses the message in a request: HTTP-POST for POST requests, HTTP-Redirect otherwise.
     *
     * saml2 4.x reads `$_POST` and `$_SERVER['QUERY_STRING']` directly, so they're set from the request for the
     * duration of the parse (they already match on real requests).
     */
    public static function receive(?Request $request = null): SamlMessage
    {
        $request ??= request();
        $post = $_POST;
        $queryString = $_SERVER['QUERY_STRING'] ?? null;

        try {
            if ($request->isMethod('POST')) {
                $_POST = $request->request->all();

                return (new HTTPPost)->receive();
            }

            $_SERVER['QUERY_STRING'] = (string) $request->server('QUERY_STRING', '');

            return (new HTTPRedirect)->receive();
        } finally {
            $_POST = $post;
            if ($queryString === null) {
                unset($_SERVER['QUERY_STRING']);
            } else {
                $_SERVER['QUERY_STRING'] = $queryString;
            }
        }
    }

    /**
     * Sends a message to the provider's endpoint for it, preferring HTTP-POST.
     *
     * @throws HttpResponseException carrying the redirect or POST form (always)
     */
    public static function send(SamlMessage $message, AbstractProvider $provider): never
    {
        static::determineSendBinding($message, $provider)->send($message);

        throw new \LogicException('The SAML binding didn’t send a response.');
    }

    protected static function determineSendBinding(SamlMessage $message, AbstractProvider $provider): HTTPPost|HTTPRedirect
    {
        $endpoint = match (true) {
            $message instanceof AuthnRequest => $provider->firstIdpSsoService(Constants::BINDING_HTTP_POST) ?? $provider->firstIdpSsoService(),
            $message instanceof Response => $provider->firstSpAcsService(Constants::BINDING_HTTP_POST) ?? $provider->firstSpAcsService(),
            $message instanceof LogoutRequest, $message instanceof LogoutResponse => static::getSLOEndpoint($provider),
            default => null,
        };

        if (! $endpoint instanceof EndpointType) {
            throw new InvalidMetadata('The provider’s metadata has no endpoint for this message.');
        }

        return $endpoint->getBinding() === Constants::BINDING_HTTP_POST ? new HTTPPost : new HTTPRedirect;
    }

    protected static function getSLOEndpoint(AbstractProvider $provider): ?EndpointType
    {
        return $provider->getType() === $provider::TYPE_IDP
            ? ($provider->firstIdpSloService(Constants::BINDING_HTTP_POST) ?? $provider->firstIdpSloService())
            : ($provider->firstSpSloService(Constants::BINDING_HTTP_POST) ?? $provider->firstSpSloService());
    }
}
