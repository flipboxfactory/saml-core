<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/23/18
 * Time: 9:26 PM
 */

namespace flipbox\saml\core\models;


use craft\base\Model;
use craft\helpers\UrlHelper;
use LightSaml\SamlConstants;

class AbstractSettings extends Model
{

    /**
     * @var string
     */
    protected $entityId;

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


}