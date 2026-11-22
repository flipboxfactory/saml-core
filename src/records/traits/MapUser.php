<?php

declare(strict_types=1);

namespace flipbox\saml\core\records\traits;

use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\renderObjectTemplate;

/**
 * @property string|null $nameIdOverride
 */
trait MapUser
{
    /**
     * The NameID to send for a user: their username, or the provider's `nameIdOverride` object template.
     */
    public function assignNameId(User $user): string
    {
        if ($this->nameIdOverride) {
            return renderObjectTemplate($this->nameIdOverride, $user);
        }

        return (string) $user->username;
    }
}
