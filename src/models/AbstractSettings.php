<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/23/18
 * Time: 9:26 PM
 */

namespace flipbox\saml\core\models;


use craft\base\Model;
use craft\config\GeneralConfig;
use craft\helpers\UrlHelper;

class AbstractSettings extends Model
{

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var array
     */
    private $environments = [];

    /**
     * @var string
     */
    private $environment = null;

    /**
     * @var array
     */
    private $defaultEnvironments = [];

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    public $loginRequestEndpoint = '/sso/login/request';

    /**
     * This is the endpoint used to initiate logout. In this case, `logoutPath` cannot be used.
     * Point your logout button to this endpoint.
     *
     * @var string
     */
    public $logoutRequestEndpoint = '/sso/login/request';

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'entityId',
                'defaultEnvironments',
                'environments',
                'environment'
            ]
        );
    }

    /*******************************************
     * ENTITY ID
     *******************************************/

    /**
     * @param $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getEntityId()
    {
        if (! $this->entityId) {
            $this->entityId = UrlHelper::baseUrl();
        }

        return $this->entityId;
    }


    /*******************************************
     * ENVIRONMENTS
     *******************************************/

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        if ($this->environment === null) {
            $this->environment = \Craft::$app->getConfig()->env;
        }

        return $this->environment;
    }

    /**
     * @param string $environment
     * @return $this
     */
    public function setEnvironment(string $environment)
    {
        $this->environment = $environment;
        return $this;
    }

    /**
     * @return array
     */
    public function getEnvironments(): array
    {
        if (empty($this->environments)) {
            $this->environments[] = \Craft::$app->getConfig()->env;
        }

        return $this->environments;
    }

    /**
     * @param array $environments
     * @return $this
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultEnvironments(): array
    {
        if (empty($this->defaultEnvironments)) {
            $this->defaultEnvironments[] = \Craft::$app->getConfig()->env;
        }

        return array_intersect(
            $this->getEnvironments(),
            $this->defaultEnvironments
        );
    }

    /**
     * @param array $environments
     * @return $this
     */
    public function setDefaultEnvironments(array $environments)
    {
        $this->defaultEnvironments = $environments;
        return $this;
    }

}