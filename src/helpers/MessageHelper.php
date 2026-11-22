<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use SAML2\Message as SamlMessage;
use SAML2\Request as SamlRequest;
use SAML2\StatusResponse as SamlResponse;
use SAML2\XML\saml\Issuer;

/**
 * Class MessageHelper
 */
class MessageHelper
{
    public const REQUEST_PARAMETER = 'SAMLRequest';

    public const RESPONSE_PARAMETER = 'SAMLResponse';

    public const ID_LENGTH = 43;

    public static function generateId(): string
    {
        return '_'.bin2hex(openssl_random_pseudo_bytes((int) ((self::ID_LENGTH - 1) / 2)));
    }

    /**
     * @return bool
     */
    public static function isResponse(SamlMessage $message)
    {
        return $message instanceof SamlResponse;
    }

    /**
     * @return bool
     */
    public static function isRequest(SamlMessage $message)
    {
        return $message instanceof SamlRequest;
    }

    /**
     * @return string
     */
    public static function getParameterKeyByMessage(SamlMessage $message)
    {
        return static::isRequest($message) ? static::REQUEST_PARAMETER : static::RESPONSE_PARAMETER;
    }

    public static function getIssuer(Issuer|string|null $issuer): ?string
    {
        if ($issuer instanceof Issuer) {
            return $issuer->getValue();
        }

        return $issuer;
    }
}
