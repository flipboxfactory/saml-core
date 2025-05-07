<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;

/**
 * m190502_200148_encryption_settings migration.
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
abstract class m190502_200148_encryption_settings extends Migration
{

    abstract protected static function getProviderTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->addColumn(
            static::getProviderTableName(),
            'groupOptions',
            $this->text()
        );
        $this->addColumn(
            static::getProviderTableName(),
            'syncGroups',
            $this->boolean()->defaultValue(true)->notNull()
        );
        $this->addColumn(
            static::getProviderTableName(),
            'groupsAttributeName',
            $this->string(64)->defaultValue(AbstractProvider::DEFAULT_GROUPS_ATTRIBUTE_NAME)
        );
        $this->addColumn(
            static::getProviderTableName(),
            'encryptAssertions',
            $this->boolean()->defaultValue(false)->notNull()
        );

        $this->addColumn(
            static::getProviderTableName(),
            'encryptionMethod',
            $this->string(64)->null()
        );
        $this->addColumn(
            static::getProviderTableName(),
            'nameIdOverride',
            $this->text()
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn(
            static::getProviderTableName(),
            'groupOptions'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'syncGroups'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'groupsAttributeName'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'encryptAssertions'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'encryptionMethod'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'nameIdOverride'
        );
        return true;
    }
}
