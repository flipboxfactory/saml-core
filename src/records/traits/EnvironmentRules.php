<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/patron/license
 * @link       https://www.flipboxfactory.com/software/patron/
 */

namespace flipbox\saml\core\records\traits;

use flipbox\ember\helpers\ModelHelper;
use flipbox\patron\Patron;
use flipbox\saml\core\SamlPluginInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;

/**
 * @property string|null $environment
 *
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 * @method SamlPluginInterface getSamlPlugin
 */
trait EnvironmentRules
{
    /**
     * @inheritdoc
     */
    protected function environmentRules()
    {
        return [
            [
                [
                    'environment'
                ],
                'required'
            ],
            [
                [
                    'environment'
                ],
                'default',
                'value' => $this->getSamlPlugin()->getSettings()->getEnvironment()
            ],
            [
                [
                    'environment'
                ],
                'safe',
                'on' => [
                    ModelHelper::SCENARIO_DEFAULT
                ]
            ]
        ];
    }
}
