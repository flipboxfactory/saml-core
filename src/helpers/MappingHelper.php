<?php

declare(strict_types=1);

namespace flipbox\saml\core\helpers;

use flipbox\saml\core\fields\AbstractExternalIdentity;

class MappingHelper
{
    /**
     * Whether a user field can be used in attribute mapping (the external identity field can't).
     */
    public static function isSupportedField(object $field): bool
    {
        return ! $field instanceof AbstractExternalIdentity;
    }
}
