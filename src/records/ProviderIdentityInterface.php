<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:30 PM
 */

namespace flipbox\saml\core\records;

use yii\db\ActiveRecordInterface;

/**
 * Interface ProviderIdentityInterface
 * @package flipbox\saml\core\records
 * @property int $providerId
 * @property int $userId
 * @property string $nameId
 * @property string $sessionId
 * @property bool $enabled
 * @property \DateTime $lastLoginDate
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @method array getErrors($attribute = null)
 */
interface ProviderIdentityInterface extends ActiveRecordInterface
{

}
