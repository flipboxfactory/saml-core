<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;


use Craft;
use craft\helpers\UrlHelper;
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
     */
    public function actionIndex()
    {

        $variables = $this->getBaseVariables();

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()),
                'label' => 'SSO Provider'
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getHandle()) . '/metadata',
                'label' => 'Metadata List'
            ],
        ];
        $variables['myProvider'] = null;
        $variables['providers'] = [];

        foreach ($this->getProviderRecord()::find()->all() as $provider) {
            $variables['providers'][] = $provider;
            if ($provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }
        $variables['pluginHandle'] = $this->getSamlPlugin()->getHandle();

        $variables['title'] = Craft::t($this->getSamlPlugin()->getHandle(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX,
            $variables
        );
    }

}