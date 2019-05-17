<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\helpers;

use flipbox\saml\core\records\ProviderInterface;

class ProviderHelper
{
    public static function providerMappingToKeyValue(ProviderInterface $provider)
    {
        $mapping = $provider->getMapping();
        if (! is_array($mapping)) {
            return [];
        }

        $newMap = [];
        foreach ($mapping as $map) {
            $newMap[$map['attributeName']] = $map['craftProperty'];
        }

        return $newMap;
    }
}
