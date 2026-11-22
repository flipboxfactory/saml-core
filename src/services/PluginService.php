<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;

/**
 * Base for services that belong to one SAML plugin. Each plugin has its own instances (see
 * {@see AbstractPlugin::service()}), so saml-sp and saml-idp can be installed together.
 */
abstract class PluginService implements EnsureSAMLPlugin
{
    public function __construct(protected AbstractPlugin $plugin) {}

    public function getPlugin(): AbstractPlugin
    {
        return $this->plugin;
    }
}
