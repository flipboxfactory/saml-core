<?php

namespace flipbox\saml\core\migrations;

use craft\db\ActiveQuery;
use craft\db\Migration;
use craft\helpers\ArrayHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;

/**
 */
abstract class m201029_200000_keychain_link_provideruid extends Migration
{

    /**
     * @return ActiveQuery
     */
    abstract protected function providerRecordQuery();
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get all of the records
        $providerRecords = $this->providerRecordQuery()->all();
        $linkRecords = ArrayHelper::index(LinkRecord::find()->all(), 'providerId');

        // update the indexes
        $this->dropForeignKey(
            $this->db->getForeignKeyName(
                LinkRecord::tableName(),
                [
                    'providerId',
                ],
                false,
                true
            ),
            LinkRecord::tableName()
        );

        $this->dropIndex(
            $this->db->getIndexName(
                LinkRecord::tableName(),
                [
                    'providerId',
                    'keyChainId',
                ],
                true,
                true
            ),
            LinkRecord::tableName()
        );

        $this->addColumn(
            LinkRecord::tableName(),
            'providerUid',
            $this->string(40)
        );

        // update the records with the uid
        /** @var AbstractProvider $record */
        foreach($providerRecords as $record) {
            /** @var LinkRecord $link */
            $link = $linkRecords[$record->id];
            $link->providerUid = $record->uid;

            $link->save();
        }

        $this->createIndex(
            $this->db->getIndexName(
                LinkRecord::tableName(),
                [
                    'providerUid',
                    'keyChainId',
                ],
                true,
                true
            ),
            LinkRecord::tableName(),
            [
                'providerUid',
                'keyChainId',
            ],
            true
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
