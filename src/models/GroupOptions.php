<?php


namespace flipbox\saml\core\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;

class GroupOptions extends Model implements \JsonSerializable
{
    const OPTION_SYNC = 'sync';
    const OPTION_DENY = 'deny';

    /**
     * @var int[]
     */
    private $sync = [];

    /**
     * @var int[]
     */
    private $deny = [];

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
    public function shouldSync($id)
    {
        return in_array($id, $this->sync);
    }

    /**
     * @param array $groups
     * @return GroupOptions
     */
    public function setDeny(array $groups)
    {
        return $this->setOption(self::OPTION_DENY, $groups);
    }


    /**
     * @return int[]
     */
    public function getDeny()
    {
        return $this->deny;
    }

    /**
     * @param $id
     * @return bool
     */
    public function shouldDeny($id)
    {
        return in_array($id, $this->deny);
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

        if (isset($options[self::OPTION_DENY])) {
            $this->setDeny($options[self::OPTION_DENY]);
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
        if (! in_array($option, [static::OPTION_SYNC, static::OPTION_DENY])) {
            throw new InvalidArgumentException('Option not valid.');
        }

        foreach ($groups as $group) {
            if (is_numeric($group)) {
                $this->{$option}[] = (int)$group;
            }
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
            'deny' => $this->deny,
        ];
    }
}
