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
use flipbox\saml\sp\Saml as SamlSp;
use flipbox\saml\idp\Saml as SamlIdp;

abstract class AbstractEditController extends AbstractController
{
    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

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

    public function actionMyProvider()
    {
        $variables = $this->prepVariables();

        if ($provider = $this->getSamlPlugin()->getProvider()->findOwn()) {
            $variables['provider'] = $provider;
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

    protected function prepVariables($providerId = null)
    {
        $variables = $this->getBaseVariables();

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), $this->getSamlPlugin()->name);

        $variables['myType'] = $this->getSamlPlugin()->getMyType();
        $variables['remoteType'] = $this->getSamlPlugin()->getRemoteType();

        if ($providerId) {
            /**
             * @var ProviderInterface $provider
             */
            $provider = $this->getProviderRecord()::find()->where([
                'id' => $providerId,
            ])->one();
            $variables['provider'] = $provider;
            $variables['title'] .= ': Edit';
            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $this->getSamlPlugin()->getUniqueId() . '/' . $providerId
                ),
                'label' => $variables['provider']->entityId,
            ];
            $variables['keypair'] = $provider->getKeyChain()->one();
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

}