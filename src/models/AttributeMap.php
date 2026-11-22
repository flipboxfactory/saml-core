<?php

declare(strict_types=1);

namespace flipbox\saml\core\models;

use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\renderObjectTemplate;

/**
 * Maps a SAML attribute to a user property, or to an object template rendered against the user.
 */
class AttributeMap
{
    use JsonModel {
        JsonModel::__construct as private configure;
    }

    public ?string $craftProperty = null;

    public ?string $attributeName = null;

    public ?string $templateOverride = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    public function renderValue(User $user): mixed
    {
        if (! $this->templateOverride && ! $this->craftProperty) {
            return '';
        }

        if ($this->templateOverride) {
            return renderObjectTemplate($this->templateOverride, $user);
        }

        return $user->{$this->craftProperty};
    }
}
