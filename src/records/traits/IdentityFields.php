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
 * Trait IdentityFields
 * @package flipbox\saml\core\records\traits
 * @method ActiveQuery hasOne(string $class, array $link)
 */
trait IdentityFields
{

    /**
     * @return string
     */
    abstract protected function getProviderRecordClass();

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProvider()
    {
        return $this->hasOne($this->getProviderRecordClass(), [
            'id' => 'providerId',
        ]);
    }

}