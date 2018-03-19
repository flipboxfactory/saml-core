<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers\cp\view;


use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\Saml;
use flipbox\saml\core\traits\EnsureSamlPlugin;

abstract class AbstractGeneralController extends AbstractController
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp';

    public function actionIndex()
    {

    }

    public function actionMetadata()
    {
        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()),
                'label' => 'SSO Provider'
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()) . '/metadata',
                'label' => 'Metadata List'
            ],
        ];
        $variables['providers'] = $this->getProviderRecord()::find()->all();
        $variables['pluginHandle'] = $this->getSamlPlugin()->getUniqueId();

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'metadata',
            $variables
        );
    }

}