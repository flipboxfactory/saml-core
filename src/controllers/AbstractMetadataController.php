<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 10:47 PM
 */

namespace flipbox\saml\core\controllers;

use Craft;
use craft\records\Site;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\controllers\cp\view\metadata\AbstractEditController;
use flipbox\saml\core\controllers\cp\view\metadata\VariablesTrait;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\MetadataOptions;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use yii\web\NotFoundHttpException;

abstract class AbstractMetadataController extends AbstractController implements \flipbox\saml\core\EnsureSAMLPlugin
{

    use VariablesTrait;

    /**
     * @return string
     * @throws InvalidMetadata
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex()
    {

        $this->requireAdmin(false);

        /** @var AbstractProvider $provider */
        $provider = $this->getPlugin()->getProvider()->findByEntityId(
            $this->getPlugin()->getSettings()->getEntityId()
        )->one();

        if (! $provider) {
            throw new InvalidMetadata('Metadata for this server is missing. Please configure this plugin.');
        }

        SerializeHelper::xmlContentType();
        return $provider->toXmlString();
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionAutoCreate()
    {
        $this->requireAdmin(false);
        $this->requirePostRequest();

        $providerRecord = $this->processSaveAction();
        if (is_null($providerRecord->uid)) {
            $providerRecord->generateUid();
        }

        $entityDescriptor = $this->getPlugin()->getMetadata()->create(
            $this->getPlugin()->getSettings(),
            $providerRecord
        );

        $provider = $this->getPlugin()->getProvider()->create(
            $entityDescriptor,
            $providerRecord->keychain
        );

        $providerRecord->entityId = $provider->getEntityId();
        $providerRecord->metadata = $provider->metadata;
        $providerRecord->setMetadataModel($provider->getMetadataModel());


        if (! $this->getPlugin()->getProvider()->save($providerRecord)) {
            return $this->renderTemplate(
                $this->getPlugin()->getEditProvider()->getTemplateIndex() .
                    AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $providerRecord,
                        'keychain' => $providerRecord->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getPlugin()->getEditProvider()->prepVariables($providerRecord)
                )
            );
        }

        return $this->asSuccess(Craft::t($this->getPlugin()->getHandle(), 'Provider saved.'));
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSave()
    {

        $this->requireAdmin(false);
        $this->requirePostRequest();

        $record = $this->processSaveAction();

        if ($record->hasErrors() || ! $this->getPlugin()->getProvider()->save($record)) {
            return $this->renderTemplate(
                $this->getPlugin()->getEditProvider()->getTemplateIndex() .
                    AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getPlugin()->getEditProvider()->prepVariables($record)
                )
            );
        }

        return $this->asSuccess(Craft::t($this->getPlugin()->getHandle(), 'Provider saved.'));
    }

    /**
     * Actions
     */

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \Exception
     */
    public function actionChangeStatus()
    {

        $this->requireAdmin(false);
        $this->requirePostRequest();

        $providerId = Craft::$app->request->getRequiredBodyParam('identifier');

        $recordClass = $this->getPlugin()->getProviderRecordClass();

        /** @var ProviderInterface $record */
        $record = $recordClass::find()->where([
            'id' => $providerId,
        ])->one();

        $record->enabled = ! $record->enabled;

        if (! $this->getPlugin()->getProvider()->save($record)) {
            return $this->renderTemplate(
                $this->getPlugin()->getEditProvider()->getTemplateIndex() .
                    AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getPlugin()->getEditProvider()->prepVariables($record)
                )
            );
        }

        return $this->asSuccess(Craft::t($this->getPlugin()->getHandle(), 'Provider saved.'));
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete()
    {
        $this->requireAdmin(false);
        $this->requirePostRequest();

        $providerId = Craft::$app->request->getRequiredBodyParam('identifier');

        $recordClass = $this->getPlugin()->getProviderRecordClass();

        /** @var ProviderInterface $record */
        $record = $recordClass::find()->where([
            'id' => $providerId,
        ])->one();

        if (! $this->getPlugin()->getProvider()->delete($record)) {
            return $this->renderTemplate(
                $this->getPlugin()->getEditProvider()->getTemplateIndex() .
                    AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getPlugin()->getEditProvider()->prepVariables($record)
                )
            );
        }

        return $this->asSuccess(Craft::t($this->getPlugin()->getHandle(), 'Provider deleted'));
    }

    /**
     * @return ProviderInterface
     * @throws \Exception
     */
    protected function processSaveAction()
    {
        $providerId = Craft::$app->request->getParam('identifier');
        $entityId = Craft::$app->request->getParam('entityId');
        $keyId = Craft::$app->request->getParam('keychain');
        $providerType = Craft::$app->request->getParam('providerType');
        $providerSite = Craft::$app->request->getParam('providerSite');
        $metadata = Craft::$app->request->getParam('metadata-text');
        $metadataUrl = Craft::$app->request->getParam('metadata-url-text');
        $metadataUrlInterval = Craft::$app->request->getParam('metadata-url-interval-text');
        $mapping = Craft::$app->request->getParam('mapping', []);
        $label = Craft::$app->request->getRequiredParam('label');
        $nameIdOverride = Craft::$app->request->getParam('nameIdOverride');

        $plugin = $this->getPlugin();

        $recordClass = $this->getPlugin()->getProviderRecordClass();
        if ($providerId) {
            /** @var ProviderInterface $record */
            $record = $recordClass::find()->where([
                'id' => $providerId,
            ])->one();

            if (! $record) {
                throw new \Exception("Provider with ID: {$providerId} not found.");
            }
        } else {
            $record = new $recordClass();

            //enabled is default
            $record->enabled = true;
        }

        $record->entityId = $entityId;

        $site = null;
        if ($providerSite) {
            if (!($site = Site::findOne([
                'id' => $providerSite,
            ]))) {
                throw new \Exception("Site with ID: {$providerSite} not found.");
            }
        }
        $record->setSite($site);

        // Metadata
        if (! $metadata && $metadataUrl) {
            $metadataModel = $this->getPlugin()->getMetadata()->fetchByUrl($metadataUrl);
            $record->metadata = $metadataModel->toXML()->ownerDocument->saveXML();
            $record->setMetadataModel($metadataModel);
        } else {
            $record->metadata = $metadata;
        }

        // Mapping
        if (is_array($mapping)) {
            $record->setMapping(
                $mapping
            );
        }

        $record->providerType = $providerType;
        $record->nameIdOverride = $nameIdOverride;

        // IDP Plugin on SP Provider ONLY
        if ($this->getPlugin()->getMyType() === SettingsInterface::IDP
            &&
            $providerType === SettingsInterface::SP
        ) {
            // Encryption settings
            $record->encryptAssertions = Craft::$app->request->getParam('encryptAssertions') ?: 0;
            $record->encryptionMethod = Craft::$app->request->getParam('encryptionMethod');
            $record->setGroupOptions(
                $groupOptions = new GroupOptions([
                    'options' => Craft::$app->request->getParam('groupOptions', []) ?: [],
                ])
            );
        }

        $record->setMetadataOptions(
            new MetadataOptions([
                'url' => $metadataUrl,
                'expiryInterval' => $metadataUrlInterval,
            ])
        );

        // Group properties
        $record->syncGroups = Craft::$app->request->getParam('syncGroups') ?: 0;

        $record->groupsAttributeName =
            Craft::$app->request->getParam('groupsAttributeName') ?:
                AbstractProvider::DEFAULT_GROUPS_ATTRIBUTE_NAME;

        /**
         * check for label and add error if it's empty
         */
        if ($label) {
            $record->label = $label;
        } else {
            $record->addError('label', Craft::t($plugin->getHandle(), "Label is required."));
        }


        $keychain = null;
        if ($keyId) {
            /** @var KeyChainRecord $keychain */
            $keychain = KeyChainRecord::find()->where([
                'id' => $keyId,
            ])->one();
        }
        $record->setKeychain($keychain);

        /**
         * Metadata should exist for the remote provider
         */
        if ($plugin->getRemoteType() === $providerType && ! $record->metadata) {
            $record->addError('metadata-text', Craft::t($plugin->getHandle(), "Metadata cannot be empty."));
        }

        return $record;
    }


    /**
     * @param $keyId
     * @return \craft\web\Response|\yii\console\Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionDownloadCertificate($keyId)
    {
        $this->requireAdmin(false);

        /** @var KeyChainRecord $keychain */
        if (! $keychain = KeyChainRecord::find()->where([
            'id' => $keyId,
        ])->one()) {
            throw new NotFoundHttpException('Key not found');
        }

        return Craft::$app->response->sendContentAsFile($keychain->getDecryptedCertificate(), 'certificate.crt');
    }
}
