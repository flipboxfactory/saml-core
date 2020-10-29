<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:10 PM
 */
namespace flipbox\saml\core\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;
use flipbox\keychain\records\KeyChainRecord;

class LinkRecord extends ActiveRecord
{

    use traits\Ember;
    const TABLE_ALIAS = 'saml_provider_keychain_link';

    /**
     * @return ActiveQuery
     */
    public function getKeyChain()
    {
        return $this->hasOne(KeyChainRecord::class, [
            'keyChainId' => 'uid',
        ]);
    }
}
