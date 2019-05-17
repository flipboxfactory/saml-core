<?php

namespace flipbox\saml\core\migrations;

use craft\db\Migration;
use flipbox\saml\core\records\AbstractProvider;
use yii\db\Query;

/**
 * mm190516_200148_attribute_typo migration.
 */
abstract class m190516_200148_attribute_typo extends Migration
{

    abstract protected static function getProviderRecord(): string;

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $providerRecord = static::getProviderRecord();
        /** @var AbstractProvider[] $providers */
        $providers = $providerRecord::find()->all();

        foreach ($providers as $provider) {
            $provider->mapping = preg_replace('/attibuteName/', 'attributeName', $provider->mapping);
            $provider->save();
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
