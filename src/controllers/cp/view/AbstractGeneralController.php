<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers\cp\view;

use craft\helpers\UrlHelper;
use flipbox\saml\core\EnsureSAMLPlugin;

/**
 * Class AbstractGeneralController
 * @package flipbox\saml\core\controllers\cp\view
 */
abstract class AbstractGeneralController extends AbstractController implements EnsureSAMLPlugin
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp';

    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->actionSetup();
    }

    /**
     * @return \yii\web\Response
     */
    public function actionSetup()
    {
        $variables = $this->getBaseVariables();
        $variables['crumbs'][] = [
            'url'   => UrlHelper::cpUrl($this->getPlugin()->getHandle()),
            'label' => 'SSO Provider'
        ];

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'setup',
            $variables
        );
    }


    /**
     * @return \yii\web\Response
     */
    public function actionSettings()
    {
        $variables = $this->getBaseVariables();

        // Breadcrumbs
        $variables['crumbs'][] = [
            'url'   => UrlHelper::cpUrl($this->getPlugin()->getHandle()),
            'label' => 'SSO Provider'
        ];
        $variables['crumbs'][] = [
            'url'   => UrlHelper::cpUrl($this->getPlugin()->getHandle() . '/settings'),
            'label' => 'Settings'
        ];

        /**
         * base action path
         * @see AbstractUpdate
         */
        $variables['baseActionPath'] = $this->getBaseActionPath();

        // tell craft to make it a form
        $variables['fullPageForm'] = true;

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'settings',
            $variables
        );
    }

    /**
     * Piece together the action url
     * @return string
     */
    private function getBaseActionPath()
    {
        return implode(
            '/',
            [
                $this->getPlugin()->getHandle(),
                'settings',
            ]
        );
    }
}
