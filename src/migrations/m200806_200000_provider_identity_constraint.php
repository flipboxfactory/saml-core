<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;
use yii\db\Query;

/**
 */
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
// phpcs:disable Generic.Files.LineLength.TooLong
abstract class m200806_200000_provider_identity_constraint extends Migration
{

    abstract protected static function getIdentityTableName();

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

//        CREATE TABLE `saml_idp_provider_identity` (
//    `id` int(11) NOT NULL AUTO_INCREMENT,
//  `providerId` int(11) NOT NULL,
//  `userId` int(11) NOT NULL,
//  `nameId` varchar(255) NOT NULL,
//  `sessionId` varchar(255) DEFAULT NULL,
//  `enabled` tinyint(1) NOT NULL DEFAULT '1',
//  `lastLoginDate` datetime NOT NULL,
//  `dateUpdated` datetime NOT NULL,
//  `dateCreated` datetime NOT NULL,
//  `uid` char(36) NOT NULL DEFAULT '0',
//  PRIMARY KEY (`id`),

//  UNIQUE KEY `saml_idp_provider_identity_providerId_userId_unq_idx` (`providerId`,`userId`),
//  KEY `saml_idp_provider_identity_nameId_fk` (`nameId`),
//  KEY `saml_idp_provider_identity_userId_fk` (`userId`),
//  CONSTRAINT `saml_idp_provider_identity_providerId_fk` FOREIGN KEY (`providerId`) REFERENCES `saml_idp_providers` (`id`) ON DELETE CASCADE,
//  CONSTRAINT `saml_idp_provider_identity_userId_fk` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE

//) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        $tableName = \Craft::$app->getDb()->getSchema()->getRawTableName($this->getIdentityTableName());

        // fk
//  CONSTRAINT `saml_idp_provider_identity_userId_fk` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE
        $this->dropForeignKey(
            $tableName . '_userId_fk',
            // $this->db->getIndexName(
            //     $this->getIdentityTableName(),
            //     [
            //         'userId',
            //     ],
            //     false,
            //     true
            // ),
            $this->getIdentityTableName()
        );

        // constraint
//  CONSTRAINT `saml_idp_provider_identity_providerId_fk` FOREIGN KEY (`providerId`) REFERENCES `saml_idp_providers` (`id`) ON DELETE CASCADE,
        $this->dropForeignKey(
            $tableName . '_providerId_fk',
            // $this->db->getIndexName(
            //     $this->getIdentityTableName(),
            //     [
            //         'providerId',
            //     ],
            //     false,
            //     true
            // ),
            $this->getIdentityTableName()
        );

//  UNIQUE KEY `saml_idp_provider_identity_providerId_userId_unq_idx` (`providerId`,`userId`),
        // unq ind
        $this->dropIndex(
            $tableName . '_providerId_userId_unq_idx',
            // $this->db->getIndexName(
            //     $this->getIdentityTableName(),
            //     [
            //         'providerId',
            //         'userId',
            //     ],
            //     true
            // ),
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
