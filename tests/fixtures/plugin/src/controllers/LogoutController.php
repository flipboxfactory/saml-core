<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin\controllers;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\messages\AbstractLogoutController;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\tests\fixtures\plugin\TestPlugin;

class LogoutController extends AbstractLogoutController
{
    public function getPlugin(): AbstractPlugin
    {
        return TestPlugin::getInstance();
    }

    protected function getRemoteProvider(?string $uid = null): ?AbstractProvider
    {
        return $this->getPlugin()->getProvider()->findByIdp($uid ? ['uid' => $uid] : [])->first();
    }
}
