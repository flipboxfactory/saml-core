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
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\base\Action;

/**
 * Class AbstractGeneralController
 * @package flipbox\saml\core\controllers\cp\view
 */
abstract class AbstractSettingsController extends AbstractController
{
    use EnsureSamlPlugin;

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(
            parent::behaviors(),
            [
                'error'    => [
                    'default' => 'save'
                ],
                'redirect' => [
                    'only'    => ['save'],
                    'actions' => [
                        'save' => [200]
                    ]
                ],
                'flash'    => [
                    'actions' => [
                        'save' => [
                            200 => \Craft::t('saml-sp', "Settings successfully updated."),
                            401 => \Craft::t('saml-sp', "Failed to update settings.")
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @return array
     */
    protected function verbs(): array
    {
        return [
            'save' => ['post', 'put']
        ];
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSave()
    {
        $entityId = Craft::$app->request->getRequiredParam('entityId');
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();

        $settings = [
            'entityId' => $entityId
        ];

        Craft::$app->plugins->savePluginSettings(
            $this->getSamlPlugin(),
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
