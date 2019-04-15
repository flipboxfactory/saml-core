<?php


namespace flipbox\saml\core\helpers;

use craft\base\Field;
use craft\fields\PlainText;
use craft\fields\Date;
use craft\fields\Color;
use craft\fields\Number;
use craft\fields\Url;
use craft\fields\Lightswitch;
use craft\fields\Dropdown;
use craft\fields\Email;

class MappingHelper
{
    /**
     *
     * @param Field $field
     * @return bool
     */
    public static function isSupportedField(Field $field)
    {
        return $field instanceof PlainText ||
            $field instanceof Date ||
            $field instanceof Color ||
            $field instanceof Number ||
            $field instanceof Url ||
            $field instanceof Lightswitch ||
            $field instanceof Dropdown ||
            $field instanceof Email;
    }

}