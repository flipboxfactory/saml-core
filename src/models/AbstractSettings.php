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
     * This is the system's entity id. Environmental Variables are allowed, ie, '$ENTITY_ID' (as a string)
     * @var string
     */
    protected $entityId;

    /**
     * @var string
     */
    public $endpointPrefix = self::ENDPOINT_PREFIX;

    /**
     * This setting will destroy sessions when the Name Id matches a user with existing sessions.
     * A current user session doesn't have to exist, ie, `\Craft::$app->user->isGuest === true`.
     *
     * This can be useful if the LogoutRequest is sent over AJAX.
     *
     * Warning: this will delete all current sessions for the user
     *
     * @var bool
     */
    public $sloDestroySpecifiedSessions = false;

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

    /**
     * @return string
     * @throws \craft\errors\SiteNotFoundException
     */
    public function getEntityIdRaw()
    {
        if (! $this->entityId) {
            $this->entityId = UrlHelper::baseUrl();
        }

        return $this->entityId;
    }

    public function getEndpointPrefix()
    {
        return \Craft::parseEnv($this->endpointPrefix);
    }

    //@todo get rid of my type here. Should be back on the plugin
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
}
