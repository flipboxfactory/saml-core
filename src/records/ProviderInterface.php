<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:30 PM
 */

namespace flipbox\saml\core\records;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\models\SettingsInterface;
use SAML2\XML\md\EntityDescriptor;
use yii\db\ActiveQuery;
use yii\db\ActiveRecordInterface;

/**
 * Interface ProviderInterface
 * @package flipbox\saml\core\records
 * @property int $id
 * @property string $label
 * @property int $entityId
 * @property string $metadata
 * @property string $sha256
 * @property string $providerType
 * @property string $mapping
 * @property string|null $nameIdOverride
 * @property string $groupOptions
 * @property bool $syncGroups
 * @property bool $groupsAttributeName
 * @property bool $encryptAssertions
 * @property string $encryptionMethod
 * @property KeyChainRecord|null $keychain
 * @property bool $enabled
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property \DateTime $uid
 */
interface ProviderInterface extends ActiveRecordInterface
{

    const TYPE_IDP = SettingsInterface::IDP;
    const TYPE_SP = SettingsInterface::SP;

    /**
     * @return EntityDescriptor
     */
    public function getMetadataModel();

    /**
     * @param EntityDescriptor $descriptor
     * @return $this
     */
    public function setMetadataModel(EntityDescriptor $descriptor);

    /**
     * @return string either idp or sp
     */
    public function getType();

    /**
     * @return ActiveQuery
     */
    public function getKeyChain();

    /**
     * @param KeyChainRecord $keyChain
     * @return $this
     */
    public function setKeychain(KeyChainRecord $keyChain);


    /**
     * @return string
     */
    public function getEntityId();

    /**
     * @return array
     */
    public function getMapping();

    /**
     * @return string
     */
    public function toXmlString();
}
