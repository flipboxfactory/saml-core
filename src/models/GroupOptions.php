<?php


namespace flipbox\saml\core\models;

use craft\elements\User;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class GroupOptions extends Model implements \JsonSerializable
{
    const OPTION_SYNC = 'sync';
    const OPTION_ALLOW = 'allow';

    /**
     * @var int[]
     */
    private $sync = [];

    /**
     * @var int[]
     */
    private $allow = [];

    /**
     * @param array $groups
     * @return GroupOptions
     */
    public function setSync(array $groups)
    {
        return $this->setOption(self::OPTION_SYNC, $groups);
    }

    /**
     * @return int[]
     */
    public function getSync()
    {
        return $this->sync;
    }

    /**
     * @param $id
     * @return bool
     */
    public function shouldSync($id): bool
    {
        return in_array($id, $this->sync);
    }

    /**
     * @param array $groups
     * @return GroupOptions
     */
    public function setAllow(array $groups)
    {
        return $this->setOption(self::OPTION_ALLOW, $groups);
    }


    /**
     * @return int[]
     */
    public function getAllow()
    {
        return $this->allow;
    }

    /**
     * @param $id
     * @return bool
     */
    public function shouldAllow($id): bool
    {
        return in_array($id, $this->allow);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function shouldDenyNoGroupAssigned(User $user): bool
    {
        return empty($user->getGroups()) && in_array('nogroup', $this->allow);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {

        if (isset($options[self::OPTION_SYNC])) {
            $this->setSync($options[self::OPTION_SYNC]);
        }

        if (isset($options[self::OPTION_ALLOW])) {
            $this->setAllow($options[self::OPTION_ALLOW]);
        }

        return $this;
    }

    /**
     * @param $option
     * @param array $groups
     * @return $this
     */
    private function setOption($option, array $groups)
    {
        if (! in_array($option, [static::OPTION_SYNC, static::OPTION_ALLOW])) {
            throw new InvalidArgumentException('Option not valid.');
        }

        foreach ($groups as $group) {
            if (empty($group)) {
                continue;
            }
            $this->{$option}[] = is_numeric($group) ? (int)$group : $group;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'sync' => $this->sync,
            'allow' => $this->allow,
        ];
    }
}
