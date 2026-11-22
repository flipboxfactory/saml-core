<?php

declare(strict_types=1);

namespace flipbox\saml\core\exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The user isn't allowed to log in through SAML (HTTP 403).
 */
class AccessDenied extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}
