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
    public function actionIndex($providerId = null)
    {
        $variables = $this->prepVariables($providerId);

        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(),
            'Remote Provider (' . strtoupper($variables['remoteType']) . ')');
        $variables['createType'] = $variables['remoteType'];

        if (isset($variables['provider']) && $variables['provider'] instanceof ProviderInterface) {

            /**
             * Actions
             */
            $variables['actions'] = $this->getActions($variables['provider']);

        }

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

    /**
     * @return \yii\web\Response
     */
    public function actionMyProvider()
    {
        $variables = $this->prepVariables();

        if ($provider = $this->getSamlPlugin()->getProvider()->findOwn()) {
            $variables['provider'] = $provider;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $provider = $variables['provider'];
            $variables['provider']->entityId = $this->getSamlPlugin()->getSettings()->getEntityId();
            $variables['provider']->providerType = $this->getSamlPlugin()->getMyType();
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

        $variables['environment'] = CRAFT_ENVIRONMENT;

        if ($providerId) {
            /**
             * @var ProviderInterface $provider
             */
            $variables['provider'] = $provider = $this->getProviderRecord()::find()->where([
                'id' => $providerId,
            ])->one();

            $variables['title'] .= ': Edit';

            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $this->getSamlPlugin()->getHandle() . '/' . $providerId
                ),
                'label' => $variables['provider']->entityId,
            ];

            $variables['keypair'] = $provider->keychain;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $record = $this->getProviderRecord();

            $variables['provider'] = new $record([
                'providerType' => 'idp',
            ]);

            $variables['title'] .= ': Create';

            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $this->getSamlPlugin()->getHandle() . '/new'
                ),
                'label' => 'New',
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

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name),
            ],
            $crumb,
        ];

        return $variables;
    }
}