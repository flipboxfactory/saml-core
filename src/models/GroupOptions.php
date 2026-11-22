<?php

declare(strict_types=1);

namespace flipbox\saml\core\models;

use CraftCms\Cms\User\Elements\User;
use InvalidArgumentException;

/**
 * Which user groups may sign in to a service provider (allow), and which are sent to it (sync).
 *
 * Group IDs, plus {@see self::ALLOW_ANY} (every user) and {@see self::NO_GROUP} (users in no group) in `allow`.
 */
class GroupOptions implements \JsonSerializable
{
    use JsonModel;

    public const OPTION_SYNC = 'sync';

    public const OPTION_ALLOW = 'allow';

    public const NO_GROUP = 'nogroup';

    public const ALLOW_ANY = 'allowany';

    /**
     * @var list<int|string>
     */
    private array $sync = [];

    /**
     * @var list<int|string>
     */
    private array $allow = [];

    /**
     * @param  array<array-key, mixed>  $groups
     */
    public function setSync(array $groups): static
    {
        return $this->setOption(self::OPTION_SYNC, $groups);
    }

    /**
     * @return list<int|string>
     */
    public function getSync(): array
    {
        return $this->sync;
    }

    public function shouldSync(int|string $id): bool
    {
        return in_array(is_numeric($id) ? (int) $id : $id, $this->sync, true);
    }

    /**
     * @param  array<array-key, mixed>  $groups
     */
    public function setAllow(array $groups): static
    {
        return $this->setOption(self::OPTION_ALLOW, $groups);
    }

    /**
     * @return list<int|string>
     */
    public function getAllow(): array
    {
        return $this->allow;
    }

    public function shouldAllow(int|string $id): bool
    {
        return in_array(is_numeric($id) ? (int) $id : $id, $this->allow, true);
    }

    public function shouldAllowNoGroupAssigned(User $user): bool
    {
        return empty($user->getGroups()) && in_array(static::NO_GROUP, $this->allow, true);
    }

    public function shouldAllowAny(): bool
    {
        return in_array(static::ALLOW_ANY, $this->allow, true);
    }

    /**
     * @param  array{sync?: array<array-key, mixed>, allow?: array<array-key, mixed>}  $options
     */
    public function setOptions(array $options): static
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
     * @param  array<array-key, mixed>  $groups
     */
    private function setOption(string $option, array $groups): static
    {
        if (! in_array($option, [static::OPTION_SYNC, static::OPTION_ALLOW], true)) {
            throw new InvalidArgumentException('Option not valid.');
        }

        foreach ($groups as $group) {
            if (empty($group) || ! is_scalar($group)) {
                continue;
            }

            $this->{$option}[] = is_numeric($group) ? (int) $group : (string) $group;
        }

        return $this;
    }

    /**
     * @return array{sync: list<int|string>, allow: list<int|string>}
     */
    public function jsonSerialize(): array
    {
        return [
            'sync' => $this->sync,
            'allow' => $this->allow,
        ];
    }
}
