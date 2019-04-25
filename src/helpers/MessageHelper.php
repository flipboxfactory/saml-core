<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use craft\helpers\StringHelper;
use SAML2\Message as SamlMessage;
use SAML2\Request as SamlRequest;
use SAML2\StatusResponse as SamlResponse;

/**
 * Class MessageHelper
 * @package flipbox\saml\core\helpers
 */
class MessageHelper
{
    const REQUEST_PARAMETER = 'SAMLRequest';
    const RESPONSE_PARAMETER = 'SAMLResponse';

    public static function generateId()
    {
        return StringHelper::UUID();
    }

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isResponse(SamlMessage $message)
    {
        return $message instanceof SamlResponse;
    }

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isRequest(SamlMessage $message)
    {
        return $message instanceof SamlRequest;
    }

    /**
     * @param SamlMessage $message
     * @return string
     */
    public static function getParameterKeyByMessage(SamlMessage $message)
    {
        return static::isRequest($message) ? static::REQUEST_PARAMETER : static::RESPONSE_PARAMETER;
    }

}
