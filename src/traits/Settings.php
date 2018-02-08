<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/23/18
 * Time: 9:26 PM
 */

namespace flipbox\saml\core\traits;


use craft\helpers\UrlHelper;

trait Settings
{
    /**
     * @var string
     */
    public $keyPath;

    /**
     * @var string
     */
    public $certPath;

    /**
     * @var string
     */
    public $entityId;

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
     */
    public function getEntityId()
    {
        if (! $this->entityId) {
            $this->entityId = UrlHelper::baseUrl();
        }


        return $this->entityId;
    }

}