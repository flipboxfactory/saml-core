<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 3:08 PM
 */

namespace flipbox\saml\core\records\traits;


use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\LinkRecord;
use yii\db\ActiveQuery;

/**
 * Trait ProviderFields
 * @package flipbox\saml\core\records\traits
 * @method ActiveQuery hasOne(string $class, array $link)
 */
trait ProviderFields
{

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLink()
    {
        return $this->hasOne(LinkRecord::class, [
            'providerId' => 'id',
        ]);
    }

    public function getKeychain()
    {
        return $this->hasOne(KeyChainRecord::class, [
            'id' => 'keyChainId',
        ])->viaTable(LinkRecord::tableName(),[
            'providerId' => 'id',
        ]);
    }
}