<?php

declare(strict_types=1);

namespace flipbox\saml\core\tests\fixtures\plugin;

use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\services\AbstractProviderService;

class ProviderService extends AbstractProviderService
{
    public function findOwn(): ?AbstractProvider
    {
        return $this->findByEntityId($this->getPlugin()->getSettings()->getEntityId())->where('enabled', true)->first();
    }
}
