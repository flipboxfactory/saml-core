<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin\controllers;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\cp\view\AbstractGeneralController;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;

class GeneralController extends AbstractGeneralController
{
    public function getPlugin(): AbstractPlugin
    {
        return TestPlugin::getInstance();
    }
}
