<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;

/**
 * mm190516_200148_attribute_typo migration.
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
abstract class m190516_200148_attribute_typo extends Migration
{
    abstract protected static function getProviderRecord(): string;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        /**
         * @var AbstractProvider $record
         */
        $record = static::getProviderRecord();
        $sql = sprintf(
            'UPDATE %s SET mapping=REPLACE(mapping, \'attibuteName\', \'attributeName\')',
            \Craft::$app->db->getSchema()->getRawTableName($record::tableName())
        );

        \Craft::$app->db->createCommand($sql)->execute();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        /**
         * @var AbstractProvider $record
         */
        $record = static::getProviderRecord();

        $sql = sprintf(
            'UPDATE %s SET mapping=REPLACE(mapping, \'attributeName\', \'attibuteName\')',
            \Craft::$app->db->getSchema()->getRawTableName($record::tableName())
        );

        \Craft::$app->db->createCommand($sql)->execute();
        return true;
    }
}
