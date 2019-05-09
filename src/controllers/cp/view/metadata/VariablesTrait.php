<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\helpers\MappingHelper;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\ProviderInterface;

/**
 * Trait VariablesTrait
 * @package flipbox\saml\core\controllers\cp\view\metadata
 * @method AbstractPlugin getPlugin
 */
trait VariablesTrait
{

    /**
     * @param ProviderInterface $provider
     * @return array
     */
    protected function getActions(ProviderInterface $provider)
    {
        $actions = [];

        if ($provider->id) {
            $actions = [
                [
                    //action list 1
                    [
                        'action' => $this->getPlugin()->getHandle() . '/metadata/change-status',
                        'label' => $provider->enabled ? 'Disable' : 'Enable',
                    ],
                    [
                        'action' => $this->getPlugin()->getHandle() . '/metadata/delete',
                        'label' => 'Delete',
                    ],
                ],
            ];
        }
        return $actions;
    }

    /**
     * @return array
     */
    protected function getBaseVariables()
    {

        return array_merge(
            parent::getBaseVariables(),
            [
                'autoCreate' => false,
                'myEntityId' => $this->getPlugin()->getSettings()->getEntityId(),
                'myType' => $this->getPlugin()->getSettings(),
            ]
        );
    }

    /**
     * @param string|null|ProviderInterface $provider
     * @return array
     */
    protected function prepVariables($provider = null)
    {
        $variables = $this->getBaseVariables();

        $variables['title'] = Craft::t(
            $this->getPlugin()->getHandle(),
            $this->getPlugin()->name
        );


        $user = new User();

        $variables['craftMappingOptions'] = $this->getCraftMappingOptions();

        /**
         * TYPES
         */
        $variables['myType'] = $this->getPlugin()->getMyType();
        $variables['remoteType'] = $this->getPlugin()->getRemoteType();
        $variables['createType'] = $variables['remoteType'];

        if ($provider) {
            /**
             * @var ProviderInterface $provider
             */
            $provider = $variables['provider'] = (
                /**
                 * Is instance provider
                 */
            $provider instanceof ProviderInterface ?
                $provider :
                $provider = $this->getPlugin()->getProviderRecordClass()::find()->where([
                    /**
                     * Is ID
                     */
                    'id' => $provider,
                ])->one()
            );

            $variables['title'] .= ': Edit';

            $crumb = [
                [

                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $this->getPlugin()->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ], [
                    'url' => UrlHelper::cpUrl(
                        $this->getPlugin()->getHandle() . '/metadata/' . $provider->id
                    ),
                    'label' => $provider->label ?: $provider->entityId,
                ],
            ];
            $variables['keypair'] = $provider->keychain;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $record = $this->getPlugin()->getProviderRecordClass();

            $provider = $variables['provider'] = new $record([
                'providerType' => SettingsInterface::IDP,
            ]);

            $variables['title'] .= ': Create';

            $crumb = [
                [
                    'url' => UrlHelper::cpUrl(
                        $this->getPlugin()->getHandle() . '/new'
                    ),
                    'label' => 'New',
                ],
            ];
        }

        $variables['allkeypairs'] = [];

        $keypairs = KeyChain::getInstance()->getService()->findByPlugin($this->getPlugin())->all();

        foreach ($keypairs as $keypair) {
            $variables['allkeypairs'][] = [
                'label' => $keypair->description,
                'value' => $keypair->id,
            ];
        }

        $variables['crumbs'] = array_merge([
            [
                'url' => UrlHelper::cpUrl($this->getPlugin()->getHandle()),
                'label' => Craft::t($this->getPlugin()->getHandle(), $this->getPlugin()->name),
            ],
        ], $crumb);

        return $variables;
    }

    protected function getCraftMappingOptions()
    {
        $user = new User();
        $options = [
            [
                'label' => $user->getAttributeLabel('firstName'),
                'value' => 'firstName',
            ],
            [
                'label' => $user->getAttributeLabel('lastName'),
                'value' => 'lastName',
            ],
            [
                'label' => $user->getAttributeLabel('email'),
                'value' => 'email',
            ],
        ];
        foreach ($user->getFieldLayout()->getFields() as $field) {
            if (MappingHelper::isSupportedField($field)) {
                $options[] = [
                    'label' => $field->name,
                    'value' => $field->handle,
                ];
            }
        }

        return $options;
    }

}
