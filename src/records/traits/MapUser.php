<?php


namespace flipbox\saml\core\records\traits;

use craft\elements\User;
use flipbox\saml\core\records\AbstractProvider;

/**
 * Trait MapUser
 * @package flipbox\saml\core\records\traits
 * @mixin AbstractProvider
 */
trait MapUser
{
    /**
     * Current only used on the IDP side with this provider being the SP
     *
     * @param User $user
     * @return string|null
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function assignNameId(User $user)
    {
        // Defaults to username
        $nameId = $user->username;

        if ($this->nameIdOverride) {
            $nameId = \Craft::$app->view->renderObjectTemplate(
                $this->nameIdOverride,
                $user
            );
        }

        return $nameId;
    }
}
