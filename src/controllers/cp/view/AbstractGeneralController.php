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

/**
 * Class AbstractGeneralController
 * @package flipbox\saml\core\controllers\cp\view
 */
abstract class AbstractGeneralController extends AbstractController
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
            'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()),
            'label' => 'SSO Provider'
        ];

        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'setup',
            $variables
        );
    }

}