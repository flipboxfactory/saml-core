<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers;

use Craft;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
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

        $settings = static::mergeStoredSettings($plugin->handle, [
            'entityId' => Craft::$app->request->getRequiredParam('entityId'),
            'endpointPrefix' => Craft::$app->request->getRequiredParam('endpointPrefix'),
        ]);

        Craft::$app->plugins->savePluginSettings(
            $plugin,
            $settings
        );
        return $this->redirectToPostedUrl();
    }

    /**
     * Merges the posted settings over the plugin's settings stored in project config.
     *
     * Since Craft 4.16.18 / 5.8.22, `Plugins::savePluginSettings()` only writes the keys it is
     * given to project config, so passing just the posted fields would drop every other stored
     * setting. Values that only come from the plugin's config file are deliberately not included,
     * so they don't get copied into project config.
     *
     * @param string $handle The plugin handle
     * @param array $posted The settings posted from the settings page
     * @return array
     */
    public static function mergeStoredSettings(string $handle, array $posted): array
    {
        $stored = Craft::$app->getProjectConfig()->get(
            ProjectConfig::PATH_PLUGINS . '.' . $handle . '.settings'
        );

        if (!is_array($stored)) {
            $stored = [];
        }

        return array_merge(
            ProjectConfigHelper::unpackAssociativeArrays($stored),
            $posted
        );
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
