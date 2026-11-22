<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin;

use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\AbstractProvider;

class ProviderRecord extends AbstractProvider
{
    public const TABLE_ALIAS = 'saml_test_providers';

    public function getLoginPath(): string
    {
        return UrlHelper::buildEndpointUrl($this->getDefaultSettings(), UrlHelper::LOGIN_ENDPOINT, $this, false);
    }

    public function getLogoutPath(): string
    {
        return UrlHelper::buildEndpointUrl($this->getDefaultSettings(), UrlHelper::LOGOUT_ENDPOINT, $this, false);
    }

    protected function getDefaultSettings(): AbstractSettings
    {
        return TestPlugin::getInstance()->getSettings();
    }
}
