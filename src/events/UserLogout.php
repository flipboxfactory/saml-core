<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\events;

use SAML2\LogoutRequest;
use yii\base\Event;

/**
 * Class UserLogout
 * @package flipbox\saml\core\events
 */
class UserLogout extends Event
{

    /**
     * @var LogoutRequest
     */
    public $request;
}
