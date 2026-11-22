<?php

declare(strict_types=1);

namespace flipbox\saml\core\events;

use flipbox\saml\core\AbstractPlugin;
use SAML2\LogoutRequest;

/**
 * A LogoutRequest was created. Listeners may change it before it's signed and sent.
 */
class LogoutRequestCreated
{
    public function __construct(
        public readonly AbstractPlugin $plugin,
        public readonly LogoutRequest $request,
    ) {}
}
