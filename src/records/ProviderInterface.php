<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:30 PM
 */

namespace flipbox\saml\core\records;

use flipbox\keychain\records\KeyChainRecord;
use LightSaml\Model\Metadata\EntityDescriptor;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

/**
 * Interface ProviderInterface
 * @package flipbox\saml\core\records
 * @property int $id
 * @property int $userId
 * @property int $entityId
 * @property string $metadata
 * @property string $sha256
 * @property string $providerType
 * @property KeyChainRecord|null $keychain
 * @property bool $enabled
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property \DateTime $uid
 */
interface ProviderInterface extends ActiveRecordInterface
{

    const TYPE_IDP = 'idp';
    const TYPE_SP = 'sp';

    /**
     * @return EntityDescriptor
     */
    public function getMetadataModel(): EntityDescriptor;

    /**
     * @return string either idp or sp
     */
    public function getType(): string;

    /**
     * @return ActiveQuery
     */
    public function getKeyChain();

    /**
     * @param KeyChainRecord $keyChain
     * @return $this
     */
    public function setKeychain(KeyChainRecord $keyChain);

}