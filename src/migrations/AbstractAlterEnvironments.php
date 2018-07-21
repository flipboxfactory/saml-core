<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use yii\db\ActiveQuery;
use flipbox\saml\core\traits\EnsureSamlPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class AbstractAlterEnvironments extends Migration
{
    use EnsureSamlPlugin;

    /**
     * The state column name
     */
    const COLUMN_NAME = 'environment';

    /**
     * Returns the class name of the Record
     * @return string
     */
    abstract protected static function getProviderEnvironmentRecord();

    /**
     * @inheritdoc
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function safeUp()
    {
        $environments = $this->getSamlPlugin()->getSettings()->getEnvironments();

        $this->deleteOldEnvironments($environments);

        $type = $this->enum(
            self::COLUMN_NAME,
            $environments
        )->notNull();

        $this->alterColumn(
            static::getProviderEnvironmentRecord()::tableName(),
            self::COLUMN_NAME,
            $type
        );

    }

    /**
     * @param array $environments
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    protected function deleteOldEnvironments(array $environments)
    {
        $this->deleteOldProviderEnvironments($environments);
    }

    /**
     * @param array $environments
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private function deleteOldProviderEnvironments(array $environments)
    {
        $recordClass = static::getProviderEnvironmentRecord();

        /** @var ActiveQuery $activeQuery */
        $activeQuery = $recordClass::find();

        $records = $activeQuery
            ->andWhere([
                'NOT IN',
                self::COLUMN_NAME,
                $environments
            ])
            ->all();

        foreach ($records as $record) {
            $record->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return false;
    }
}
