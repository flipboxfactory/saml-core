<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 3:08 PM
 */

namespace flipbox\saml\core\records\traits;


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
            'id' => 'providerId',
        ]);
    }

    public function getKeychain()
    {
        return $this->getLink()->with('keychain');
    }
}