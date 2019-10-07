<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/28/18
 * Time: 9:45 PM
 */

namespace flipbox\saml\core\records;

use craft\validators\DateTimeValidator;
use craft\db\ActiveRecord;
use flipbox\saml\core\records\traits\Ember;
use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Class AbstractProviderIdentity
 * @package flipbox\saml\core\records
 * @property int $userId
 * @property bool $enabled
 * @property string $sessionId
 */
abstract class AbstractProviderIdentity extends ActiveRecord implements ProviderIdentityInterface
{
    use Ember;
    /**
     * @var \craft\elements\User
     */
    private $user;

    /**
     * @return \craft\elements\User|null
     */
    public function getUser()
    {
        if (! $this->userId) {
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'lastLoginDate',
                    ],
                    DateTimeValidator::class,
                ],
                [
                    [
                        'lastLoginDate',
                        'sessionId',
                    ],
                    'safe',
                    'on' => [
                        Model::SCENARIO_DEFAULT,
                    ]
                ]
            ]
        );
    }
}
