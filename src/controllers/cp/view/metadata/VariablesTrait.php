<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use craft\helpers\UrlHelper;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;

trait VariablesTrait
{
    use EnsureSamlPlugin;

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
                        'action' => $this->getSamlPlugin()->getHandle() . '/metadata/change-status',
                        'label'  => $provider->enabled ? 'Disable' : 'Enable',
                    ],
                    [
                        'action' => $this->getSamlPlugin()->getHandle() . '/metadata/delete',
                        'label'  => 'Delete',
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
                'myEntityId' => $this->getSamlPlugin()->getSettings()->getEntityId(),
                'myType'     => $this->getSamlPlugin()->getSettings(),
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
            $this->getSamlPlugin()->getHandle(),
            $this->getSamlPlugin()->name
        );

        /**
         * TYPES
         */
        $variables['myType'] = $this->getSamlPlugin()->getMyType();
        $variables['remoteType'] = $this->getSamlPlugin()->getRemoteType();
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
                $provider = $this->getSamlPlugin()->getProviderRecordClass()::find()->where([
                    /**
                     * Is ID
                     */
                    'id' => $provider,
                ])->one()
            );

            $variables['title'] .= ': Edit';

            $crumb = [
                [

                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $this->getSamlPlugin()->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ], [
                    'url'   => UrlHelper::cpUrl(
                        $this->getSamlPlugin()->getHandle() . '/metadata/' . $provider->id
                    ),
                    'label' => $provider->label ?: $provider->entityId,
                ]
            ];
            $variables['keypair'] = $provider->keychain;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $record = $this->getSamlPlugin()->getProviderRecordClass();

            $provider = $variables['provider'] = new $record([
                'providerType' => 'idp',
            ]);

            $variables['title'] .= ': Create';

            $crumb = [
                [
                    'url'   => UrlHelper::cpUrl(
                        $this->getSamlPlugin()->getHandle() . '/new'
                    ),
                    'label' => 'New',
                ]
            ];
        }

        $variables['allkeypairs'] = [];

        $keypairs = KeyChain::getInstance()->getService()->findByPlugin($this->getSamlPlugin())->all();

        foreach ($keypairs as $keypair) {
            $variables['allkeypairs'][] = [
                'label' => $keypair->description,
                'value' => $keypair->id,
            ];
        }

        $variables['crumbs'] = array_merge([
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name),
            ],
        ], $crumb);

        return $variables;
    }
}
