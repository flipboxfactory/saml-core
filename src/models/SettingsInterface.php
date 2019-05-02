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
     * Receives logout requests
     * Returns a URL
     *
     * @return string
     */
    public function getDefaultLogoutEndpoint();

    /**
     * Initiates logout requests
     * Returns a URL
     *
     * @return string
     */
    public function getDefaultLogoutRequestEndpoint();

    /**
     * Receives login requests
     * Returns a URL
     *
     * @return string
     */
    public function getDefaultLoginEndpoint();

    /**
     * Initiates login request
     * Returns a URL
     *
     * @return string
     */
    public function getDefaultLoginRequestEndpoint();

    /**
     * @return string
     */
    public function getMyType();

}
