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

abstract class AbstractSettings extends Model implements SettingsInterface
{

    const ENDPOINT_PREFIX = 'sso';

    /**
     * @var string
     */
    protected $myType;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var string
     */
    public $endpointPrefix = self::ENDPOINT_PREFIX;

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    protected $loginEndpoint = 'login';

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    protected $logoutEndpoint = 'logout';

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    protected $loginRequestEndpoint = 'login/request';

    /**
     * This is the endpoint used to initiate logout. In this case, `logoutPath` cannot be used.
     * Point your logout button to this endpoint.
     *
     * @var string
     */
    protected $logoutRequestEndpoint = 'logout/request';

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'entityId',
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

        return \Craft::parseEnv($this->entityId);
    }

    public function getEndpointPrefix()
    {
        return \Craft::parseEnv($this->endpointPrefix);
    }

    /**
     * @param $myType
     * @return $this
     */
    public function setMyType($myType)
    {
        $this->myType = $myType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMyType()
    {
        return $this->myType;
    }

    /**
     * @return string
     */
    public function getRemoteType()
    {
        return $this->getMyType() === self::IDP ? self::SP : self::IDP;
    }

    /**
     * @return bool
     */
    public function isIDP()
    {
        return $this->getMyType() === self::IDP;
    }

    /**
     * @return bool
     */
    public function isSP()
    {
        return $this->getMyType() === self::SP;
    }

    protected function buildEndpointUrl($url)
    {
        return sprintf('/%s/%s', $this->getEndpointPrefix(), $url);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLoginEndpoint()
    {
        return UrlHelper::siteUrl(
            $this->buildEndpointUrl(
                $this->loginEndpoint
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLogoutEndpoint()
    {
        return UrlHelper::siteUrl(
            $this->buildEndpointUrl($this->logoutEndpoint)
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLogoutRequestEndpoint()
    {
        return UrlHelper::siteUrl(
            $this->buildEndpointUrl($this->logoutRequestEndpoint)
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLoginRequestEndpoint()
    {
        return UrlHelper::siteUrl(
            $this->buildEndpointUrl($this->loginRequestEndpoint)
        );
    }
}
