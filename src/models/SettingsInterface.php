<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/22/18
 * Time: 3:38 PM
 */

namespace flipbox\saml\core\models;

interface SettingsInterface
{
    const IDP = 'idp';
    const SP = 'sp';

    /**
     * @return string
     */
    public function getEntityId();

    /**
     * @return string
     */
    public function getMyType();
}
