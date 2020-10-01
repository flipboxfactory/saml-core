<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers;

use Craft;
use craft\helpers\ArrayHelper;
use flipbox\saml\core\AbstractPlugin;

/**
 * Class AbstractGeneralController
 * @package flipbox\saml\core\controllers\cp\view
 */
abstract class AbstractSettingsController extends AbstractController implements \flipbox\saml\core\EnsureSAMLPlugin
{

    /**
     * @return array
     */
    protected function verbs(): array
    {
        return [
            'save' => ['post', 'put'],
        ];
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSave()
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getPlugin();
        
        $settings = [
            'entityId' => Craft::$app->request->getRequiredParam('entityId'),
            'endpointPrefix' => Craft::$app->request->getRequiredParam('endpointPrefix'),
        ];

        Craft::$app->plugins->savePluginSettings(
            $this->getPlugin(),
            $settings
        );
        return $this->redirectToPostedUrl();
    }

    /**
     * @return bool
     * @throws \yii\web\ForbiddenHttpException
     */
    public function checkUpdateAccess(): bool
    {
        return $this->checkAdminAccess();
    }
}
