<?php

declare(strict_types=1);

namespace flipbox\saml\core\helpers;

use Illuminate\Http\Response;

class SerializeHelper
{
    /**
     * An XML response, e.g. for metadata.
     */
    public static function xmlResponse(string $xml): Response
    {
        return new Response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }

    /**
     * Appends query parameters to a URL that may already have some.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function redirectUrl(string $location, array $parameters): string
    {
        if ($parameters === []) {
            return $location;
        }

        return $location.(str_contains($location, '?') ? '&' : '?').http_build_query($parameters);
    }
}
