<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 2:30 PM
 */

namespace flipbox\saml\core\records;

use LightSaml\Model\Metadata\EntityDescriptor;
use yii\db\ActiveRecordInterface;

/**
 * Interface ProviderInterface
 * @package flipbox\saml\core\records
 * @property int $id
 * @property int $entityId
 * @property string $metadata
 * @property string $sha256
 * @property string $propertyType
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
}