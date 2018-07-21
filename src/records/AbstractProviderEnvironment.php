<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/patron/license
 * @link       https://www.flipboxfactory.com/software/patron/
 */

namespace flipbox\saml\core\records;

use flipbox\ember\helpers\ModelHelper;
use flipbox\ember\helpers\QueryHelper;
use flipbox\ember\records\ActiveRecord;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\db\ActiveQueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property int $providerId
 * @property string $environment
 */
abstract class AbstractProviderEnvironment extends ActiveRecord
{
    use EnsureSamlPlugin, traits\EnvironmentAttribute;

    /**
     * The table alias
     */
    const TABLE_ALIAS = '';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            $this->environmentRules(),
            [
                [
                    [
                        'providerId'
                    ],
                    'number',
                    'integerOnly' => true
                ],
                [
                    [
                        'providerId'
                    ],
                    'required'
                ],
                [
                    [
                        'providerId'
                    ],
                    'safe',
                    'on' => [
                        ModelHelper::SCENARIO_DEFAULT
                    ]
                ]
            ]
        );
    }

    /**
     * @param array $config
     * @return \yii\db\ActiveQuery
     */
    public function getProvider(array $config = [])
    {
        $query = $this->hasOne(
            get_class($this->getSamlPlugin()),
            ['providerId' => 'id']
        );

        if (! empty($config)) {
            QueryHelper::configure(
                $query,
                $config
            );
        }

        return $query;
    }
}
