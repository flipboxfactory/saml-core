<?php

declare(strict_types=1);

namespace flipbox\saml\core;

interface EnsureSAMLPlugin
{
    public function getPlugin(): AbstractPlugin;
}
