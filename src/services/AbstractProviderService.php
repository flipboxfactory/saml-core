<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 12:12 AM
 */

namespace flipbox\saml\core\services;

use craft\base\Component;
use craft\helpers\Json;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\records\ProviderInterface;
use SAML2\XML\md\EntityDescriptor;

abstract class AbstractProviderService extends Component implements ProviderServiceInterface, EnsureSAMLPlugin
{
    /**
     * @inheritdoc
     * @deprecated
     */
    abstract public function findOwn();

    /**
     * @inheritdoc
     */
    public function find($condition = [])
    {
        /** @var AbstractProvider $class */
        $class = $this->getPlugin()->getProviderRecordClass();

        if (! $provider = $class::find()->where($condition)) {
            return null;
        }

        return $provider;
    }

    /**
     * @inheritdoc
     */
    public function findByIdp($condition = [])
    {
        return $this->findByType('idp', $condition);
    }

    /**
     * @inheritdoc
     */
    public function findBySp($condition = [])
    {
        return $this->findByType('sp', $condition);
    }

    /**
     * @inheritdoc
     */
    protected function findByType($type, $condition = [])
    {
        if (! in_array($type, ['sp', 'idp'])) {
            throw new \InvalidArgumentException("Type must be idp or sp.");
        }
        return $this->find(
            array_merge(
                [
                    'enabled' => 1,
                    'providerType' => $type,
                ],
                $condition
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function findByEntityId($entityId)
    {
        return $this->find([
            'entityId' => $entityId,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function create(EntityDescriptor $entityDescriptor, ?KeyChainRecord $keyChainRecord = null): ProviderInterface
    {

        $recordClass = $this->getPlugin()->getProviderRecordClass();

        $provider = (new $recordClass())
            ->loadDefaultValues();


        $provider->providerType = $this->getPlugin()->getMyType();

        $provider->setMetadataModel($entityDescriptor);

        \Craft::configure($provider, [
            'entityId' => $entityDescriptor->getEntityID(),
            'metadata' => $provider->toXmlString(),
        ]);

        if ($keyChainRecord) {
            $provider->setKeychain($keyChainRecord);
        }

        return $provider;
    }

    /**
     * @inheritdoc
     */
    public function save(ProviderInterface $record, $runValidation = true, $attributeNames = null)
    {
        if ($record->isNewRecord) {
            $record->loadDefaultValues();
        }

        //save record
        if (! $record->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }

        if ($record->keychain) {
            $this->linkToKey(
                $record,
                $record->keychain
            );
        } else {
            LinkRecord::deleteAll(['providerId' => $record->id]);
        }

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function linkToKey(
        AbstractProvider $provider,
        KeyChainRecord $keyChain,
        $runValidation = true,
        $attributeNames = null
    ) {
        if (! $provider->id && ! $keyChain->id) {
            throw new \Exception('Provider id and keychain id must exist before linking.');
        }
        $linkAttributes = [
            'providerId' => $provider->id,
            'providerUid' => $provider->uid,
        ];

        /** @var LinkRecord $link */
        if (! $link = LinkRecord::find()->where($linkAttributes)->one()) {
            $link = new LinkRecord($linkAttributes);
        }

        $linkAttributes['keyChainId'] = $keyChain->id;
        \Craft::configure(
            $link,
            $linkAttributes
        );
        if (! $link->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($provider->getErrors()));
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(ProviderInterface $provider)
    {
        return $provider->delete();
    }
}
