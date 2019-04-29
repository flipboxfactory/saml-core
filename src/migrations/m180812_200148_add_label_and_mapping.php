<?php

namespace flipbox\saml\core\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180812_200148_add_provider_label migration.
 */
abstract class m180812_200148_add_label_and_mapping extends Migration
{

    abstract protected static function getProviderTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            static::getProviderTableName(),
            'label',
            $this->string(64)->after('id')
        );

        $this->addColumn(
            static::getProviderTableName(),
            'mapping',
            $this->text()->after('providerType')
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
            'label'
        );
        $this->dropColumn(
            static::getProviderTableName(),
            'mapping'
        );
        return true;
    }
}
