<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin;

use flipbox\saml\core\models\AbstractSettings;

class Settings extends AbstractSettings
{
    protected ?string $myType = self::SP;
}
