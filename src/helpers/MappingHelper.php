<?php


namespace flipbox\saml\core\helpers;

use craft\base\Field;
use flipbox\saml\core\fields\AbstractExternalIdentity;

class MappingHelper
{
    /**
     *
     * @param Field $field
     * @return bool
     */
    public static function isSupportedField(Field $field)
    {

        return ! $field instanceof AbstractExternalIdentity;
    }

}