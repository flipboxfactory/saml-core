<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services;


use Craft;
use craft\base\Component;
use flipbox\saml\core\migrations\AbstractAlterEnvironments;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\base\Model;

abstract class AbstractCp extends Component
{

    use EnsureSamlPlugin;

    /**
     * @return AbstractAlterEnvironments
     */
    abstract protected function createNewMigration(): AbstractAlterEnvironments;

    /**
     * @param SettingsInterface $settingsModel
     * @return bool
     * @throws \Throwable
     */
    public function saveSettings(SettingsInterface $settingsModel)
    {
        /** @var Model $settingsModel */

        // Save plugin settings
        if (Craft::$app->getPlugins()->savePluginSettings(
            $this->getSamlPlugin(),
            $settingsModel->toArray()
        )) {
            // Alter table
            return $this->alterEnvironmentsColumn();
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    private function alterEnvironmentsColumn(): bool
    {
        $migration = $this->createNewMigration();

        ob_start();
        $migration->up();
        ob_end_clean();

        return true;
    }
}