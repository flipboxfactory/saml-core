<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin\controllers;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use flipbox\keychain\controllers\cp\view\AbstractGeneralController;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;

class KeychainIndexController extends AbstractGeneralController
{
    protected function getPlugin(): PluginInterface
    {
        return TestPlugin::getInstance();
    }
}
