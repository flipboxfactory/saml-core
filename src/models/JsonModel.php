<?php

declare(strict_types=1);

namespace flipbox\saml\core\models;

/**
 * Array-configured, JSON-serializable option models stored in provider columns.
 *
 * `new Model(['key' => $value])` calls `setKey($value)` when it exists, otherwise sets a public `$key` property;
 * unknown keys are ignored, so stored JSON from older versions still loads.
 */
trait JsonModel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $setter = 'set'.ucfirst((string) $key);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists($this, (string) $key) && (new \ReflectionProperty($this, (string) $key))->isPublic()) {
                $this->$key = $value;
            }
        }
    }

    public function __toString(): string
    {
        return (string) json_encode($this);
    }

    public static function jsonUnserialize(?string $json): static
    {
        $data = $json ? json_decode($json, true) : [];

        return new static(is_array($data) ? $data : []); // @phpstan-ignore new.static (models are constructed by config)
    }
}
