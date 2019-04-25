<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 10:51 PM
 */

namespace flipbox\saml\core\traits;

use flipbox\saml\core\AbstractPlugin;

trait EnsureSamlPlugin
{
    /**
     * @return AbstractPlugin
     */
    abstract protected function getSamlPlugin(): AbstractPlugin
}
