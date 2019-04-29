<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services;

use Craft;
use craft\base\Component;
use flipbox\saml\core\migrations\AbstractAlterEnvironments;
use flipbox\saml\core\models\SettingsInterface;
use yii\base\Model;
use flipbox\saml\core\EnsureSAMLPlugin

abstract class AbstractCp extends Component implements EnsureSAMLPlugin
{

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
            $this->getPlugin(),
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
