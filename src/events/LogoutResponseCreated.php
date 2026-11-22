<?php

declare(strict_types=1);

namespace flipbox\saml\core\events;

use flipbox\saml\core\AbstractPlugin;
use SAML2\LogoutResponse;

/**
 * A LogoutResponse was created. Listeners may change it before it's signed and sent.
 */
class LogoutResponseCreated
{
    public function __construct(
        public readonly AbstractPlugin $plugin,
        public readonly LogoutResponse $response,
    ) {}
}
