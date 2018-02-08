<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/25/18
 * Time: 3:32 PM
 */

namespace flipbox\saml\core\services\traits;


trait Metadata
{

    /**
     * @return string
     */
    abstract public function getLogoutResponseLocation();

    /**
     * @return string
     */
    abstract public function getLogoutRequestLocation();

    /**
     * @return string
     */
    abstract public function getLoginLocation();

}