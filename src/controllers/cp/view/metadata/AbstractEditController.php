<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/10/18
 * Time: 8:40 PM
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use craft\helpers\UrlHelper;
use flipbox\saml\core\records\ProviderInterface;

/**
 * Class AbstractEditController
 * @package flipbox\saml\core\controllers\cp\view\metadata
 */
abstract class AbstractEditController extends AbstractController
{
    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    /**
     * @param string|null $providerId
     * @return \yii\web\Response
     */
    public function actionIndex($providerId = null, $overwriteVariables = [])
    {
        $variables = $this->prepVariables($providerId);
        $provider = $variables['provider'];

        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(),
            'Edit ' . $this->getTitle($provider->getType())
        );

        $variables['createType'] = $variables['remoteType'];

        if (isset($variables['provider']) && $variables['provider'] instanceof ProviderInterface) {

            /**
             * Actions
             */
            $variables['actions'] = $this->getActions($variables['provider']);

        }

        $variables = array_merge($variables, $overwriteVariables);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

    /**
     * @return \yii\web\Response
     */
    public function actionNewIdp()
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();
        return $this->actionIndex(null, [
            'title'      => 'New ' . $this->getTitle($plugin::IDP),
            'createType' => $plugin::IDP,
            'crumbs'     => [
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                            ]
                        )
                    ),
                    'label' => $plugin->name,
                ],
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ],
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                                'new-idp'
                            ]
                        )
                    ),
                    'label' => 'New IDP',
                ],
            ],
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionNewSp()
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();
        return $this->actionIndex(null, [
            'title'      => 'New ' . $this->getTitle($plugin::SP),
            'createType' => $plugin::SP,
            'crumbs'     => [
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                            ]
                        )
                    ),
                    'label' => $plugin->name,
                ],
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ],
                [
                    'url'   => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                                'new-sp'
                            ]
                        )
                    ),
                    'label' => 'New SP',
                ],
            ],
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionMyProvider()
    {
        $provider = $this->getSamlPlugin()->getProvider()->findOwn();
        $variables = $this->prepVariables(
            $provider ? $provider->id : null
        );

        if ($provider) {
            $variables['provider'] = $provider;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $provider = $variables['provider'];
            $variables['provider']->entityId = $this->getSamlPlugin()->getSettings()->getEntityId();
            $variables['provider']->providerType = $this->getSamlPlugin()->getMyType();
            $variables['provider']->label = 'My Provider';
        }

        /**
         * Actions
         */
        $variables['actions'] = $this->getActions($provider);

        /**
         * Edit Title
         */
        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(),
            'My Provider (' . strtoupper($variables['provider']->providerType) . ')');

        $variables['createType'] = $variables['myType'];

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

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
     * @param string|null $providerId
     * @return array
     */
    protected function prepVariables($providerId = null)
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

        if ($providerId) {
            /**
             * @var ProviderInterface $provider
             */
            $provider = $variables['provider'] = $provider = $this->getProviderRecord()::find()->where([
                'id' => $providerId,
            ])->one();

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
                        $this->getSamlPlugin()->getHandle() . '/metadata/' . $providerId
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
            $record = $this->getProviderRecord();

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