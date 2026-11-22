<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\messages;

use flipbox\saml\core\containers\UtilizeSaml2Container;
use flipbox\saml\core\EnsureSAMLPlugin;

/**
 * Base for controllers that receive or send SAML messages. Loads the plugin's saml2 container first.
 */
abstract class AbstractController implements EnsureSAMLPlugin, UtilizeSaml2Container
{
    public function __construct()
    {
        $this->loadContainer();
    }

    public function loadContainer(): void
    {
        $this->getPlugin()->loadSaml2Container();
    }
}
