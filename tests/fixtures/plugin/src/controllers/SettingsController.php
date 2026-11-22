<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin\controllers;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\AbstractSettingsController;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;

class SettingsController extends AbstractSettingsController
{
    public function getPlugin(): AbstractPlugin
    {
        return TestPlugin::getInstance();
    }
}
