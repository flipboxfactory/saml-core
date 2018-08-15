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

    /**
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionIndex()
    {
        $variables = $this->getBaseVariables();
        $plugin = $this->getSamlPlugin();
        $variables['myProvider'] = null;
        $variables['spProviders'] = [];
        $variables['idpProviders'] = [];
        $variables['idpListInstructions'] = $this->getListInstructions($plugin::IDP);
        $variables['spListInstructions'] = $this->getListInstructions($plugin::SP);

        /**
         * Breadcrumbs
         */
        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => $this->getSamlPlugin()->name
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'Provider List'
            ],
        ];

        /**
         * Get IDPs
         */
        foreach ($plugin->getProvider()->findByIdp([
            'enabled' => [true, false],
        ])->all() as $provider) {
            $variables['idpProviders'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }

        /**
         * Get SPs
         */
        foreach ($plugin->getProvider()->findBySp([
            'enabled' => [true, false],
        ])->all() as $provider) {
            $variables['spProviders'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }

        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'list',
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
            'These are your CraftCMS sites (this website). '
        ) : Craft::t(
            $this->getSamlPlugin()->getHandle(),
            'These are the remote providers where the user ' .
            'authenticates, ie, OKTA, Microsoft AD, or Google, etc. To configure and IDP, simply obtain the metadata.'
        );
    }
}