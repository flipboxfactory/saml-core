<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\fields;


use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\User;
use flipbox\saml\core\records\ProviderIdentityInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\db\Query;

abstract class AbstractExternalIdentity extends Field
{
    use EnsureSamlPlugin;

    public static function displayName(): string
    {
        return 'External Identity';
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (! ($element instanceof User)) {
            return null;
        }
        /** @var User $element */
        return $this->getSamlPlugin()->getProviderIdentity()->findByUser($element);
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return $this->getStaticHtml($value, $element);
    }

    /**
     * @param $value
     * @param ElementInterface $element
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        if (! ($value instanceof Query)) {
            return '';
        }
        return \Craft::$app->getView()->renderTemplate(
            'saml-core/_cp/fields/external-id',
            [
                'identities' => $value,
                'element'    => $element,
            ]
        );
//        return "
//<div class='meta'>
//    NameID: {$value->nameId}
//    <br/>
//    Issuer: {$value->provider->getEntityId()}
//    <br/>
//    Last Login: {$value->lastLoginDate}
//</div >
//";
    }
}