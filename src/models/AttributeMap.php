<?php


namespace flipbox\saml\core\models;

use craft\elements\User;
use yii\base\Model;

class AttributeMap extends Model
{
    /**
     * @var string
     */
    public $craftProperty;
    /**
     * @var string
     */
    public $attributeName;
    /**
     * @var string
     */
    public $templateOverride;

    public function renderValue(User $user)
    {
        $value = null;
        if ($this->templateOverride) {
            $value = \Craft::$app->view->renderObjectTemplate(
                $this->templateOverride,
                $user
            );
        } else {
            $value = $user->{$this->craftProperty};
        }

        return $value;
    }
}