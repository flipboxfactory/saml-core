<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin\controllers;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use flipbox\keychain\controllers\cp\view\AbstractEditController;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;

class KeychainEditController extends AbstractEditController
{
    protected function getPlugin(): PluginInterface
    {
        return TestPlugin::getInstance();
    }
}
