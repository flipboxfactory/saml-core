<?php

namespace flipbox\saml\core\migrations;

use Craft;
use craft\db\Migration;
use flipbox\saml\core\traits\EnsureSamlPlugin;

/**
 * m180812_200148_add_provider_label migration.
 */
abstract class m180812_200148_add_provider_label extends Migration
{

    use EnsureSamlPlugin;

    abstract static protected function getProviderTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            static::getProviderTableName(),
            'label',
            $this->string(64)
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
        return true;
    }
}
