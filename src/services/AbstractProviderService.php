<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 12:12 AM
 */

namespace flipbox\saml\core\services;


use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderEnvironment;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Metadata\EntityDescriptor;

abstract class AbstractProviderService extends Component implements ProviderServiceInterface
{

    use EnsureSamlPlugin;
    /**
     * @var AbstractProvider[]
     */
    private $cache = [];

    /**
     * @return string
     */
    abstract public function getRecordClass();

    /**
     * @return string
     */
    abstract public function getEnvironmentRecordClass();

    /**
     * @inheritdoc
     */
    abstract public function findOwn();

    /**
     * @inheritdoc
     */
    public function find($condition = [])
    {
        /** @var AbstractProvider $class */
        $class = $this->getRecordClass();
        if (isset($condition['entityId']) && isset($this->cache[$condition['entityId']])) {

            return $this->cache[$condition['entityId']];
        }

        if (! $provider = $class::find()->where($condition)->one()) {
            return null;
        }

        /** @var AbstractProvider $provider */
        $this->cache[$provider->getEntityId()] = $provider;
        return $provider;
    }

    /**
     * @inheritdoc
     */
    public function findByIdp()
    {
        return $this->findByType('idp');
    }

    /**
     * @inheritdoc
     */
    public function findBySp()
    {
        return $this->findByType('sp');
    }

    /**
     * @inheritdoc
     */
    protected function findByType($type)
    {
        if (! in_array($type, ['sp', 'idp'])) {
            throw new \InvalidArgumentException("Type must be idp or sp.");
        }
        return $this->find([
            'enabled'      => 1,
            'providerType' => $type,
        ]);
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
    public function create(EntityDescriptor $entityDescriptor, KeyChainRecord $keyChainRecord = null): ProviderInterface
    {

        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();

        /** @var ProviderInterface $provider */
        $provider = (new $recordClass())
            ->loadDefaultValues();


        $provider->providerType = $this->getSamlPlugin()->getMyType();

        \Craft::configure($provider, [
            'entityId' => $entityDescriptor->getEntityID(),
            'metadata' => SerializeHelper::toXml($entityDescriptor),
        ]);

        if ($keyChainRecord)
            $provider->setKeychain($keyChainRecord);

        return $provider;
    }

    /**
     * @inheritdoc
     */
    public function save(AbstractProvider $record, $runValidation = true, $attributeNames = null)
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
    )
    {
        if (! $provider->id && ! $keyChain->id) {
            throw new \Exception('Provider id and keychain id must exist before linking.');
        }
        $linkAttributes = [
            'providerId' => $provider->id,
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
            throw new \Exception(Json::encode($record->getErrors()));
        }
    }

    /**
     * @param AbstractProvider $provider
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function saveEnvironments(AbstractProvider $provider)
    {
        $successful = true;

        /** @var AbstractProviderEnvironment[] $allRecords */
        $allRecords = $provider->getEnvironments()
            ->all();

        foreach ($this->resolveEnvironments($provider) as $model) {
            ArrayHelper::remove($allRecords, $model->environment);
            $model->providerId = $provider->id;

            if (! $model->save()) {
                $successful = false;
                // Log the errors
                $error = \Craft::t(
                    'patron',
                    "Couldn't save environment due to validation errors:"
                );
                foreach ($model->getFirstErrors() as $attributeError) {
                    $error .= "\n- " . \Craft::t('patron', $attributeError);
                }

                $provider->addError('sites', $error);
            }
        }

        // Delete old records
        foreach ($allRecords as $record) {
            $record->delete();
        }

        return $successful;
    }

    /**
     * @param AbstractProvider $provider
     * @return AbstractProviderEnvironment[]
     */
    protected function defaultEnvironments(AbstractProvider $provider): array
    {
        $environments = [];

        $environmentRecordClass = $this->getEnvironmentRecordClass();

        foreach ($this->getSamlPlugin()->getSettings()->getDefaultEnvironments() as $environment) {
            $environments[$environment] = new $environmentRecordClass([
                'providerId'  => $provider->id,
                'environment' => $environment
            ]);
        }

        return $environments;
    }

    /**
     * @param AbstractProvider $provider
     * @return array
     */
    protected function resolveEnvironments(AbstractProvider $provider): array
    {
        $environments = $provider->environments;

        if (empty($environments)) {
            $environments = $this->defaultEnvironments($provider);
        }

        return $environments;
    }

    /**
     * @inheritdoc
     */
    public function delete(ProviderInterface $provider)
    {
        return $provider->delete();
    }
}