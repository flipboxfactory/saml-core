<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers;

use CraftCms\Cms\Http\RespondsWithFlash;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;

/**
 * Base for the SAML plugins' control panel form actions. Routes require an admin (see
 * {@see AbstractPlugin::registerFormActionRoutes()}).
 */
abstract class AbstractController implements EnsureSAMLPlugin
{
    use RespondsWithFlash;
}
