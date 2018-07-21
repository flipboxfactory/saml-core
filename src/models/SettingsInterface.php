<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/22/18
 * Time: 3:38 PM
 */

namespace flipbox\saml\core\models;


Interface SettingsInterface
{

    /**
     * @return string
     */
    public function getEntityId();

    /**
     * @return string
     */
    public function getEnvironment(): string;

    /**
     * @param string $environment
     * @return $this
     */
    public function setEnvironment(string $environment);

    /**
     * @return array
     */
    public function getEnvironments(): array;

    /**
     * @param array $environments
     * @return $this
     */
    public function setEnvironments(array $environments);

    /**
     * @return array
     */
    public function getDefaultEnvironments(): array;

    /**
     * @param array $environments
     * @return $this
     */
    public function setDefaultEnvironments(array $environments);
}