<?php

namespace flipbox\saml\core\migrations;

use craft\db\ActiveQuery;
use craft\db\Migration;
use craft\records\Site;

/**
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
abstract class m201030_200000_provider_multisite extends Migration
{

    /**
     * @return ActiveQuery
     */
    abstract protected function providerRecordTable():string;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(
            $this->providerRecordTable(),
            'siteId',
            $this->integer()->null()
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                $this->providerRecordTable(),
                'siteId'
            ),
            $this->providerRecordTable(),
            'siteId',
            Site::tableName(),
            'id'
        );

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
