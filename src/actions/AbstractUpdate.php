<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\actions;


use Craft;
use flipbox\ember\actions\model\ModelCreate;
use craft\helpers\ArrayHelper;
use flipbox\ember\exceptions\ModelNotFoundException;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\base\Model;

/**
 * Class AbstractUpdate
 * @package flipbox\saml\core\actions
 */
abstract class AbstractUpdate extends ModelCreate
{

    use EnsureSamlPlugin;

    /**
     * These are the default body params that we're accepting.  You can lock down specific Client attributes this way.
     *
     * @return array
     */
    protected function validBodyParams(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function attributeValuesFromBody(): array
    {
        $attributes = parent::attributeValuesFromBody();
        $attributes['environments'] = $this->environmentValuesFromBody();
        return $attributes;
    }

    /**
     * Normalize settings from body
     *
     * @return array
     */
    protected function environmentValuesFromBody(): array
    {
        $environmentArray = [];
        if ($rawEnvironments = Craft::$app->getRequest()->getBodyParam('environments', [])) {
            foreach (ArrayHelper::toArray($rawEnvironments) as $rawEnvironment) {
                $environmentArray = array_merge(
                    $environmentArray,
                    $this->normalizeEnvironmentValue($rawEnvironment)
                );
            }
        }
        return array_values($environmentArray);
    }


    /**
     * @param string|array $value
     * @return array
     */
    protected function normalizeEnvironmentValue($value = []): array
    {
        if (is_array($value)) {
            $value = ArrayHelper::getValue($value, 'value');
        }

        return [$value => $value];
    }

    /**
     * @param Model $model
     * @return bool
     * @throws \Throwable
     */
    protected function performAction(Model $model): bool
    {
        if (! $model instanceof SettingsInterface) {
            throw new ModelNotFoundException(sprintf(
                "Settings must be an instance of '%s', '%s' given.",
                get_class($this->getSamlPlugin()->getSettings()),
                get_class($model)
            ));
        }

        return $this->getSamlPlugin()->getCp()->saveSettings($model);
    }

    /**
     * @inheritdoc
     */
    protected function newModel(array $config = []): Model
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getSamlPlugin()->getSettings();
    }
}