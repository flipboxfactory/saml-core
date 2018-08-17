<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use flipbox\saml\core\records\ProviderInterface;
use LightSaml\Model\Protocol\AbstractRequest;

interface SamlRequestInterface
{
    /**
     * @param ProviderInterface $provider
     * @return AbstractRequest
     */
    public function create(ProviderInterface $provider, array $config = []): AbstractRequest;
}
