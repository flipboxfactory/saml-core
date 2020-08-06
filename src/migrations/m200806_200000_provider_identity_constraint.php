<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;
use yii\db\Query;

/**
 */
abstract class m200806_200000_provider_identity_constraint extends Migration
{

    abstract protected static function getIdentityTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->dropForeignKey(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'userId',
                ],
                false,
                true
            ),
            $this->getIdentityTableName()
        );

        $this->dropForeignKey(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                ],
                false,
        true
            ),
            $this->getIdentityTableName()
        );

        $this->dropIndex(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                    'userId',
                ],
                true
            ),
            $this->getIdentityTableName()
        );

        $this->createIndex(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                    'nameId',
                    'userId',
                ],
                true
            ),
            $this->getIdentityTableName(),
            [
                'providerId',
                'nameId',
                'userId',
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
        $this->dropForeignKey(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'userId',
                ],
                false,
                true
            ),
            $this->getIdentityTableName()
        );

        $this->dropForeignKey(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                ],
                false,
                true
            ),
            $this->getIdentityTableName()
        );

        $this->dropIndex(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                    'nameId',
                    'userId',
                ],
                true
            ),
            $this->getIdentityTableName()
        );

        $this->createIndex(
            $this->db->getIndexName(
                $this->getIdentityTableName(),
                [
                    'providerId',
                    'userId',
                ],
                true
            ),
            $this->getIdentityTableName(),
            [
                'providerId',
                'userId',
            ],
            true
        );
        return true;
    }
}
