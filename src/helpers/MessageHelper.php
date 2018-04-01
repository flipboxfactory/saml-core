<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;


use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\StatusResponse;

/**
 * Class MessageHelper
 * @package flipbox\saml\core\helpers
 */
class MessageHelper
{
    const REQUEST_PARAMETER = 'SAMLRequest';
    const RESPONSE_PARAMETER = 'SAMLResponse';

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isResponse(SamlMessage $message)
    {
        return $message instanceof StatusResponse;
    }

    /**
     * @param SamlMessage $message
     * @return bool
     */
    public static function isRequest(SamlMessage $message)
    {
        return $message instanceof AbstractRequest;
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