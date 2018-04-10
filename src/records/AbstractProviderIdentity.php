<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/28/18
 * Time: 9:45 PM
 */

namespace flipbox\saml\core\records;


use flipbox\ember\records\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Class AbstractProviderIdentity
 * @package flipbox\saml\core\records
 */
abstract class AbstractProviderIdentity extends ActiveRecord implements ProviderIdentityInterface
{
    /**
     * @var \craft\elements\User
     */
    private $user;

    /**
     * @return \craft\elements\User|null
     */
    public function getUser()
    {
        if(! $this->userId) {
            return null;
        }
        if (! $this->user) {
            $this->user = \Craft::$app->getUsers()->getUserById(
                $this->userId
            );
        }
        return $this->user;
    }

    /**
     * @return ActiveQuery
     */
    abstract public function getProvider();

}