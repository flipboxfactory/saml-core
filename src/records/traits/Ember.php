<?php

declare(strict_types=1);

namespace flipbox\saml\core\records\traits;

/**
 * Table naming from `TABLE_ALIAS`, and enabled/disabled helpers.
 *
 * @property bool $enabled
 */
trait Ember
{
    public static function tableAlias(): string
    {
        return static::TABLE_ALIAS; // @phpstan-ignore classConstant.notFound (defined by concrete records)
    }

    /**
     * The table name (without Laravel's table prefix, which the query builder adds).
     */
    public static function tableName(): string
    {
        return static::tableAlias();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    public function toEnabled(): static
    {
        $this->enabled = true;

        return $this;
    }

    public function toDisabled(): static
    {
        $this->enabled = false;

        return $this;
    }
}
