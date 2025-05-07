<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;
use yii\db\Query;

/**
 * mm190516_200148_attribute_typo migration.
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
abstract class m200107_200148_metadata_options extends Migration
{

    abstract protected static function getProviderTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->alterColumn(
            static::getProviderTableName(),
            'metadata',
            $this->mediumText()
        );

        if (!$this->db->columnExists(
            static::getProviderTableName(),
            'metadataOptions',
            true
        )) {
            $this->addColumn(
                static::getProviderTableName(),
                'metadataOptions',
                $this->text()
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return true;
    }
}
