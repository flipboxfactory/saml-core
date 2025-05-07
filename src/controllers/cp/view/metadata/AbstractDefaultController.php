<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use craft\helpers\UrlHelper;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\models\SettingsInterface;

/**
 * Class AbstractDefaultController
 * @package flipbox\saml\core\controllers\cp\view\metadata
 */
abstract class AbstractDefaultController extends AbstractController implements EnsureSAMLPlugin
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    /**
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function actionIndex()
    {
        $variables = $this->getPlugin()->getEditProvider()->getBaseVariables();
        $plugin = $this->getPlugin();
        $variables['myProvider'] = null;
        $variables['spProviders'] = [];
        $variables['idpProviders'] = [];
        $variables['idpListInstructions'] = $this->getListInstructions(SettingsInterface::IDP);
        $variables['spListInstructions'] = $this->getListInstructions(SettingsInterface::SP);

        /**
         * Breadcrumbs
         */
        $variables['crumbs'] = [
            [
                'url' => UrlHelper::cpUrl($this->getPlugin()->getHandle()),
                'label' => $this->getPlugin()->name,
            ],
            [
                'url' => UrlHelper::cpUrl($this->getPlugin()->getHandle() . '/metadata'),
                'label' => 'Provider List',
            ],
        ];

        /**
         * Get IDPs
         */
        foreach ($plugin->getProvider()->findByIdp([
            'enabled' => [true, false],
        ])->all() as $provider) {
            $variables['idpProviders'][] = $provider;
            if ($provider->getEntityId() == $this->getPlugin()->getSettings()->getEntityId()) {
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
            if ($provider->getEntityId() == $this->getPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }

        $variables['title'] = Craft::t($this->getPlugin()->getHandle(), $this->getPlugin()->name);
        return $this->renderTemplate(
            $this->getPlugin()->getEditProvider()->getTemplateIndex() .
                static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'list',
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
        $plugin = $this->getPlugin();
        if (! in_array($providerType, [
            SettingsInterface::SP,
            SettingsInterface::IDP,
        ])) {
            throw new \Exception($providerType . ' is not a valid type.');
        }

        /**
         * Base return off of getMyType and the provider type passed
         */
        switch ($this->getPlugin()->getMyType()) {
            case SettingsInterface::IDP:
                $return = SettingsInterface::SP === $providerType ? Craft::t(
                    $this->getPlugin()->getHandle(),
                    'These are the remote providers using this Craft CMS instance to authenticate. '
                ) : Craft::t(
                    $this->getPlugin()->getHandle(),
                    'Your provider configuration(s).'
                );
                break;
            case SettingsInterface::SP:
                $return = SettingsInterface::SP === $providerType ? Craft::t(
                    $this->getPlugin()->getHandle(),
                    'Your provider configuration(s). '
                ) : Craft::t(
                    $this->getPlugin()->getHandle(),
                    'These are the remote providers where the user ' .
                    'authenticates, ie, OKTA, Microsoft AD, or Google, etc. ' .
                    'To configure and IDP, simply obtain the metadata.'
                );
                break;
        }

        return $return;
    }
}
