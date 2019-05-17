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

    /**
     * @var string
     */
    protected $myType;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    public $loginEndpoint = '/sso/login';

    /**
     * This is the endpoint used to initiate login. Set the general.php config for `loginPath` to this.
     * @see GeneralConfig::$loginPath
     *
     * @var string
     */
    public $logoutEndpoint = '/sso/logout';

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
    public $logoutRequestEndpoint = '/sso/logout/request';

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

        return $this->entityId;
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

    /**
     * @inheritdoc
     */
    public function getDefaultLoginEndpoint()
    {
        return UrlHelper::siteUrl($this->loginEndpoint);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLogoutEndpoint()
    {
        return UrlHelper::siteUrl($this->logoutEndpoint);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLogoutRequestEndpoint()
    {
        return UrlHelper::siteUrl($this->logoutRequestEndpoint);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLoginRequestEndpoint()
    {
        return UrlHelper::siteUrl($this->loginRequestEndpoint);
    }
}
