<?php

declare(strict_types=1);

namespace flipbox\saml\core\events;

use flipbox\saml\core\AbstractPlugin;
use SAML2\XML\md\EntityDescriptor;

/**
 * This system's metadata was created. Listeners may change it before it's published.
 */
class MetadataCreated
{
    public function __construct(
        public readonly AbstractPlugin $plugin,
        public readonly EntityDescriptor $entityDescriptor,
    ) {}
}
