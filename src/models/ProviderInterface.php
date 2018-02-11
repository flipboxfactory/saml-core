<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/26/18
 * Time: 2:00 PM
 */

namespace flipbox\saml\core\models;

use LightSaml\Model\Metadata\EntityDescriptor;

interface ProviderInterface
{
    /**
     *
     * @return EntityDescriptor|null
     */
    public function getMetadataModel();
}