<?php

declare(strict_types=1);

namespace flipbox\saml\core\containers;

use Illuminate\Support\Facades\Log;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * PSR-3 logger for saml2 and the SAML plugins, writing to Laravel's log with a `category` (default `saml-core`).
 *
 * `{placeholder}` tokens in messages are replaced from the context, as PSR-3 describes. The context is otherwise
 * passed through, so never put secrets in it.
 */
class Logger extends AbstractLogger
{
    public function __construct(public string $category = 'saml-core') {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $category = $context['category'] ?? $this->category;
        unset($context['category']);

        Log::log((string) $level, self::interpolate((string) $message, $context), [...$context, 'category' => $category]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value instanceof Stringable) {
                $replace['{'.$key.'}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }
}
