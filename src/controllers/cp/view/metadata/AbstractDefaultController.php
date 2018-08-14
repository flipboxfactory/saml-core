<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;


use Craft;
use craft\helpers\UrlHelper;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\cp\view\AbstractController;

/**
 * Class AbstractDefaultController
 * @package flipbox\saml\core\controllers\cp\view\metadata
 */
abstract class AbstractDefaultController extends AbstractController
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    public function actionList()
    {
        $variables = $this->getBaseVariables();

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => $this->getSamlPlugin()->name
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'Metadata List'
            ],
        ];

        $plugin = $this->getSamlPlugin();
        $variables['myProvider'] = null;
        $variables['spProviders'] = [];
        $variables['idpProviders'] = [];
        $variables['idpListInstructions'] = $this->getListInstructions($plugin::IDP);;
        $variables['spListInstructions'] = $this->getListInstructions($plugin::SP);


        foreach ($plugin->getProvider()->findByIdp([
            'enabled' => [true, false],
        ])->all() as $provider) {
            $variables['idpProviders'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }

        foreach ($plugin->getProvider()->findBySp([
            'enabled' => [true, false],
        ])->all() as $provider) {
            $variables['spProviders'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }

        $variables['pluginHandle'] = $this->getSamlPlugin()->getHandle();

        $variables['mainAction'] = [
            'url'   => implode('/', [
                $this->getSamlPlugin()->getHandle(),
                'metadata',
                'my-provider',
            ]),
            'label' => 'Edit My Provider'
        ];
        $variables['actions'] = [
            [
                'url'   => UrlHelper::cpUrl(
                    implode('/', [
                        $this->getSamlPlugin()->getHandle(),
                        'metadata',
                        'new-sp',
                    ])
                ),
                'label' => 'Create New IDP'
            ]
        ];


        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'list',
            $variables
        );

    }

    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {

        $variables = $this->getBaseVariables();

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => $this->getSamlPlugin()->name
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'Metadata List'
            ],
        ];
        $variables['myProvider'] = null;
        $variables['providers'] = [];
        $variables['listType'] = 'all';
        $variables['listInstructions'] = '';


        foreach ($this->getProviderRecord()::find()->all() as $provider) {
            $variables['providers'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }
        $variables['pluginHandle'] = $this->getSamlPlugin()->getHandle();

        $variables['mainAction'] = [
            'url'   => implode('/', [
                $this->getSamlPlugin()->getHandle(),
                'metadata',
                'my-provider',
            ]),
            'label' => 'Edit My Provider'
        ];
        $variables['actions'] = [
            [
                'url'   => UrlHelper::cpUrl(
                    implode('/', [
                        $this->getSamlPlugin()->getHandle(),
                        'metadata',
                        'new-sp',
                    ])
                ),
                'label' => 'Create New IDP'
            ]
        ];


        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX,
            $variables
        );
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionIdp()
    {
        $variables = $this->getBaseVariables();

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => $this->getSamlPlugin()->name
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'IDP List'
            ],
        ];

        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();
        $variables['myProvider'] = null;
        $variables['providers'] = [];
        $variables['listType'] = $plugin::IDP;
        $variables['listInstructions'] = $this->getListInstructions($plugin::IDP);

        foreach ($this->getSamlPlugin()->getProvider()->findByIdp([
            'enabled' => [true, false]
        ])->all() as $provider) {
            $variables['providers'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }
        $variables['pluginHandle'] = $this->getSamlPlugin()->getHandle();

        $variables['title'] = Craft::t(
            $this->getSamlPlugin()->getHandle(),
            'Identity Provider List'
        );

        $variables['mainAction'] = [
            'url'   => implode('/', [
                $this->getSamlPlugin()->getHandle(),
                'metadata',
                'my-provider',
            ]),
            'label' => 'Edit My Provider'
        ];
        $variables['actions'] = [
            [
                'url'   => UrlHelper::cpUrl(
                    implode('/', [
                        $this->getSamlPlugin()->getHandle(),
                        'metadata',
                        'new-idp',
                    ])
                ),
                'label' => 'Create New IDP'
            ]
        ];
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX,
            $variables
        );

    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionSp()
    {
        $variables = $this->getBaseVariables();

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => $this->getSamlPlugin()->name
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'SP List'
            ],
        ];
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();
        $variables['myProvider'] = null;
        $variables['providers'] = [];
        $variables['listType'] = $plugin::SP;
        $variables['listInstructions'] = $this->getListInstructions($plugin::SP);


        foreach ($this->getSamlPlugin()->getProvider()->findBySp([
            'enabled' => [true, false]
        ])->all() as $provider) {
            $variables['providers'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }
        $variables['pluginHandle'] = $this->getSamlPlugin()->getHandle();

        $variables['title'] = Craft::t(
            $this->getSamlPlugin()->getHandle(),
            'Service Provider List'
        );
        $variables['mainAction'] = [
            'url'   => implode('/', [
                $this->getSamlPlugin()->getHandle(),
                'metadata',
                'my-provider',
            ]),
            'label' => 'Edit My Provider'
        ];
        $variables['actions'] = [
            [
                'url'   => UrlHelper::cpUrl(
                    implode('/', [
                        $this->getSamlPlugin()->getHandle(),
                        'metadata',
                        'new-sp',
                    ])
                ),
                'label' => 'Create New SP'
            ]
        ];
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX,
            $variables
        );

    }

    /**
     * @param $providerType
     * @return string
     * @throws \Exception
     */
    protected function getListInstructions($providerType)
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();
        if (! in_array($providerType, [
            $plugin::SP,
            $plugin::IDP,
        ])) {
            throw new \Exception($providerType . ' is not a valid type.');
        }

        return $plugin::SP === $providerType ? Craft::t(
            $this->getSamlPlugin()->getHandle(),
            'These are your CraftCMS sites (this website) as a provider. '
        ) : Craft::t(
            $this->getSamlPlugin()->getHandle(),
            'These are the remote providers where the user' .
            'authenticates like OKTA, Microsoft AD, or Google. To configure and IDP, simply obtain the metadata.'
        );
    }
}