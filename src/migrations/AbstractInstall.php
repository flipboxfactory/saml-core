<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:23 PM
 */

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use craft\records\Site;
use craft\records\User;
use flipbox\keychain\records\KeyChainRecord;
use yii\base\InvalidConfigException;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;

abstract class AbstractInstall extends Migration
{

    const PROVIDER_AFTER_COLUMN = 'sha256';
    protected $linkTableExist = false;

    /**
     * @return string
     */
    abstract protected function getIdentityTableName(): string;

    /**
     * @return string
     */
    abstract protected function getProviderTableName(): string;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        try {
            /**
             * if it doesn't exist, this will throw an exception
             */
            LinkRecord::getTableSchema();
            $this->linkTableExist=true;
        } catch (InvalidConfigException $e) {
            \Craft::warning('Link table doesn\'t exist. Going to create it and the indexes.');
        }

        $this->installKeyChain();
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    protected function installKeyChain()
    {
        if (! \Craft::$app->plugins->getPlugin('keychain')) {
            \Craft::$app->plugins->installPlugin('keychain');
        }
    }

    protected function getProviderFields()
    {
        return [
            'id' => $this->primaryKey(),
            'label' => $this->string(64),
            'entityId' => $this->string()->notNull(),
            'metadata' => $this->mediumText()->notNull(),
            'sha256' => $this->string()->notNull(),
            'providerType' => $this->enum('providerType', [
                SettingsInterface::SP,
                SettingsInterface::IDP,
            ])->notNull(),
            'encryptAssertions' => $this->boolean()->defaultValue(false)->notNull(),
            'encryptionMethod' => $this->string(64)->null(),
            'siteId' => $this->integer()->null(),
            'groupOptions' => $this->text(),
            'metadataOptions' => $this->text(),
            'syncGroups' => $this->boolean()->defaultValue(true)->notNull(),
            'groupsAttributeName' => $this->string(64)->defaultValue(AbstractProvider::DEFAULT_GROUPS_ATTRIBUTE_NAME),
            'nameIdOverride' => $this->text(),
            'mapping' => $this->text(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ];
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {


        $this->createTable($this->getProviderTableName(), $this->getProviderFields());

        if($this->linkTableExist === false) {
            $this->createTable(LinkRecord::tableName(), [
                'id' => $this->primaryKey(),
                'providerId' => $this->integer()->notNull(),
                'providerUid' => $this->integer()->notNull(),
                'keyChainId' => $this->integer()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        $this->createTable($this->getIdentityTableName(), [
            'id' => $this->primaryKey(),
            'providerId' => $this->integer()->notNull(),
            'userId' => $this->integer()->notNull(),
            'nameId' => $this->string()->notNull(),
            'sessionId' => $this->string()->null(),
            'enabled' => $this->boolean()->defaultValue(true)->notNull(),
            'lastLoginDate' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {


        // Delete tables
        $this->dropTableIfExists($this->getIdentityTableName());
        $this->dropTableIfExists(LinkRecord::tableName());
        $this->dropTableIfExists($this->getProviderTableName());
        return true;
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {

        $this->createIndex(
            $this->db->getIndexName($this->getProviderTableName(), 'entityId', false, true),
            $this->getProviderTableName(),
            'entityId',
            false
        );
        $this->createIndex(
            $this->db->getIndexName($this->getProviderTableName(), [
                'sha256',
            ], true, true),
            $this->getProviderTableName(),
            [
                'sha256',
            ],
            true
        );

        if($this->linkTableExist === false) {
            $this->createIndex(
                $this->db->getIndexName(LinkRecord::tableName(), [
                    'providerUid',
                    'keyChainId',
                ], true, true),
                LinkRecord::tableName(),
                [
                    'providerUid',
                    'keyChainId',
                ],
                true
            );
        }

        $this->createIndex(
            $this->db->getIndexName($this->getIdentityTableName(), 'nameId', false, true),
            $this->getIdentityTableName(),
            'nameId',
            false
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
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {

        if($this->linkTableExist === false) {
            /**
             * Link KeyChain
             */
            $this->addForeignKey(
                $this->db->getForeignKeyName(LinkRecord::tableName(), 'keyChainId'),
                LinkRecord::tableName(),
                'keyChainId',
                KeyChainRecord::tableName(),
                'id',
                'CASCADE'
            );
        }

        $this->addForeignKey(
            $this->db->getForeignKeyName($this->getIdentityTableName(), 'userId'),
            $this->getIdentityTableName(),
            'userId',
            User::tableName(),
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->getIdentityTableName(), 'providerId'),
            $this->getIdentityTableName(),
            'providerId',
            $this->getProviderTableName(),
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName(
                $this->getProviderTableName(),
                'siteId'
            ),
            $this->getProviderTableName(),
            'siteId',
            Site::tableName(),
            'id'
        );
    }
}
