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
     * @return string
     */
    abstract protected function getUpdateClass();

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
        /** @var Action $action */
        $action = Craft::createObject([
            'class'       => $this->getUpdateClass(),
            'checkAccess' => [$this, 'checkUpdateAccess']
        ], [
            'update',
            $this
        ]);

        return $action->runWithParams([]);
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