<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\fields;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\User;
use craft\helpers\UrlHelper;
use flipbox\saml\core\records\ProviderIdentityInterface;
use yii\db\Query;

abstract class AbstractExternalIdentity extends Field implements \flipbox\saml\core\EnsureSAMLPlugin
{

    public static function displayName(): string
    {
        return 'SAML External Identity';
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if (! ($element instanceof User)) {
            return null;
        }
        /** @var User $element */
        return $this->getPlugin()->getProviderIdentity()->findByUser($element);
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
        $handle = $this->getPlugin()->getHandle();

        return \Craft::$app->getView()->renderTemplate(
            $handle . '/_cp/fields/external-id',
            [
                'identities' => $value,
                'element' => $element,
                'pluginHandle' => $handle,
            ]
        );
    }
}
