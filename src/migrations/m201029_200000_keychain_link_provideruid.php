<?php

namespace flipbox\saml\core\migrations;

use craft\db\ActiveQuery;
use craft\db\Migration;
use craft\helpers\ArrayHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;

/**
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
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
        $linkRecords = [];
        foreach (LinkRecord::find()->all() as $record) {
            $linkRecords[$record->providerId] = $record;
        }

        $tableName = \Craft::$app->getDb()->getSchema()->getRawTableName(LinkRecord::tableName());
        $providerFk = $tableName . '_providerId_fk';
        $this->dropForeignKey(
            $providerFk,
            LinkRecord::tableName()
        );
        $providerKeychainFk = $tableName . '_providerId_keyChainId_unq_fk';
        $this->dropIndex(
            $providerKeychainFk,
            LinkRecord::tableName()
        );

        $this->addColumn(
            LinkRecord::tableName(),
            'providerUid',
            $this->uid()
        );

        // update the records with the uid
        /** @var AbstractProvider $record */
        foreach ($providerRecords as $record) {
            // move on if the link doesn't exist
            if (!isset($linkRecords[$record->id])) {
                continue;
            }

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
