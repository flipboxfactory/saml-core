<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\traits\EnsureSamlPlugin;

/**
 * m180711_034730_add_environment_column migration.
 */
abstract class m180711_034730_environment extends Migration
{
    use EnsureSamlPlugin;

    /**
     * @return string
     */
    abstract protected static function getProviderEnvironmentTableName();

    /**
     * @return string
     */
    abstract protected static function getProviderTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(
            static::getProviderEnvironmentTableName(),
            [
                'providerId'  => $this->integer()->notNull(),
                'environment' => $this->enum(
                    'environment',
                    $this->getSamlPlugin()->getSettings()->getEnvironments()
                )->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid(),
            ]
        );

        $this->addPrimaryKey(
            null,
            static::getProviderEnvironmentTableName(),
            [
                'providerId',
                'environment'
            ]
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                static::getProviderEnvironmentTableName(),
                'providerId'
            ),
            static::getProviderEnvironmentTableName(),
            'providerId',
            static::getProviderTableName(),
            'id',
            'CASCADE'
        );

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn(
            static::getProviderEnvironmentTableName(),
            'environment'
        );
    }
}
