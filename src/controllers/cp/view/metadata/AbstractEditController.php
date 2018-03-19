<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/10/18
 * Time: 8:40 PM
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use craft\helpers\UrlHelper;
use flipbox\saml\core\records\ProviderInterface;

abstract class AbstractEditController extends AbstractController
{
    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    public function actionIndex($providerId = null)
    {
        $variables = $this->getBaseVariables();

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), $this->getSamlPlugin()->name);

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
        $keypairs = KeyChainRecord::find()->all();
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


        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

}