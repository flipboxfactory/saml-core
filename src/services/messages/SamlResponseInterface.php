<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\StatusResponse;

interface SamlResponseInterface
{
    public function create(AbstractRequest $samlMessage, array $config = []): StatusResponse;
}
