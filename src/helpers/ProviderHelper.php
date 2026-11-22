<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use flipbox\saml\core\records\ProviderInterface;

class ProviderHelper
{
    /**
     * The provider's attribute mapping as `attributeName => craftProperty`.
     *
     * @return array<string, string>
     */
    public static function providerMappingToKeyValue(ProviderInterface $provider): array
    {
        $mapping = $provider->getMapping();

        $newMap = [];
        foreach ($mapping as $map) {
            $newMap[(string) ($map['attributeName'] ?? '')] = (string) ($map['craftProperty'] ?? '');
        }

        return $newMap;
    }
}
