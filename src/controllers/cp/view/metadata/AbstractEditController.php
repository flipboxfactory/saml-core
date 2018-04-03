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
     * @param null $providerId
     * @return \yii\web\Response
     */
    public function actionIndex($providerId = null)
    {
        $variables = $this->prepVariables($providerId);

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), 'Remote Provider (' . strtoupper($variables['remoteType']) . ')');
        $variables['createType'] = $variables['remoteType'];

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
            $variables['provider']->entityId = $this->getSamlPlugin()->getSettings()->getEntityId();
            $variables['provider']->providerType = $this->getSamlPlugin()->getMyType();
        }

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), 'My Provider (' . strtoupper($variables['provider']->providerType) . ')');

        $variables['createType'] = $variables['myType'];

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

    /**
     * @return array
     */
    protected function getBaseVariables()
    {
        return array_merge(
            [
                'autoCreate' => false,
                'myEntityId' => $this->getSamlPlugin()->getSettings()->getEntityId(),
                'myType'     => $this->getSamlPlugin()->getSettings()
            ],
            parent::getBaseVariables()
        );
    }

    /**
     * @param null $providerId
     * @return array
     */
    protected function prepVariables($providerId = null)
    {
        $variables = $this->getBaseVariables();

        $variables['title'] = Craft::t(
            $this->getSamlPlugin()->getUniqueId(),
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
            $variables['provider'] = $provider = $this->getProviderRecord()::find()->where([
                'id' => $providerId,
            ])->one();

            $variables['title'] .= ': Edit';

            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $this->getSamlPlugin()->getUniqueId() . '/' . $providerId
                ),
                'label' => $variables['provider']->entityId,
            ];

            $variables['keypair'] = $provider->getKeyChain()->one();

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
                    $this->getSamlPlugin()->getUniqueId() . '/new'
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
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()),
                'label' => Craft::t($this->getSamlPlugin()->getUniqueId(), $this->getSamlPlugin()->name),
            ],
            $crumb,
        ];

        return $variables;
    }

    /**
     * @param ProviderInterface $provider
     * @param array $variables
     * @return array
     */
    protected function addUrls(ProviderInterface $provider)
    {

        $variables = [];
        $variables['assertionConsumerServices'] = null;
        $variables['singleLogoutServices'] = null;
        $variables['singleSignOnServices'] = null;

        if (! $provider->getMetadataModel()) {
            return $variables;
        }

        $plugin = $this->getSamlPlugin();

        /**
         * Add SP URLs
         */
        if ($provider->getType() === $plugin::SP) {
            foreach (
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getAllSingleLogoutServices()
                as $singleLogoutService
            ) {
                $variables['singleLogoutServices'][$singleLogoutService->getBinding()] = $singleLogoutService->getResponseLocation();
            }

            foreach (
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getAllAssertionConsumerServices()
                as $assertionConsumerService
            ) {
                $variables['assertionConsumerServices'][$assertionConsumerService->getBinding()] = $assertionConsumerService->getLocation();
            }
        }

        /**
         * Add IDP URLs
         */
        if ($provider->getType() === $plugin::IDP) {
            foreach (
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getAllSingleLogoutServices()
                as $singleLogoutService
            ) {
                $variables['singleLogoutServices'][$singleLogoutService->getBinding()] = $singleLogoutService->getLocation();
            }

            foreach (
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getAllSingleSignOnServices()
                as $signOnService
            ) {
                $variables['singleLogoutServices'][$signOnService->getBinding()] = $signOnService->getLocation();
            }
        }

        return $variables;
    }
}