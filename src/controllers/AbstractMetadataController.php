<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 10:47 PM
 */

namespace flipbox\saml\core\controllers;


use Craft;
use flipbox\ember\exceptions\RecordNotFoundException;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\controllers\cp\view\metadata\AbstractEditController;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use yii\web\NotFoundHttpException;

abstract class AbstractMetadataController extends AbstractController
{

    use EnsureSamlPlugin;

    /**
     * @return string
     * @throws InvalidMetadata
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex()
    {

        $this->requireAdmin();

        $provider = $this->getSamlPlugin()->getProvider()->findByEntityId(
            $this->getSamlPlugin()->getSettings()->getEntityId()
        )->one();

        if ($provider) {
            $metadata = $provider->getMetadataModel();
        } else {
            throw new InvalidMetadata('Metadata for this server is missing. Please configure this plugin.');
        }

        SerializeHelper::xmlContentType();
        return SerializeHelper::toXml($metadata);
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionAutoCreate()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $record = $this->processSaveAction();

        $entityDescriptor = $this->getSamlPlugin()->getMetadata()->create(
            $record->keychain,
            $entityId = Craft::$app->request->getParam('entityId', null)
        );

        $provider = $this->getSamlPlugin()->getProvider()->create(
            $entityDescriptor,
            $record->keychain
        );

        $record->entityId = $provider->getEntityId();
        $record->metadata = $provider->metadata;
        $record->setMetadataModel($provider->getMetadataModel());


        if (! $this->getSamlPlugin()->getProvider()->save($record)) {
            return $this->renderTemplate(
                $this->getTemplateIndex() . AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getBaseVariables()
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSave()
    {

        $this->requireAdmin();
        $this->requirePostRequest();

        $record = $this->processSaveAction();
        $record->metadata = Craft::$app->request->getBodyParam('metadata');
        if (! $this->getSamlPlugin()->getProvider()->save($record)) {
            return $this->renderTemplate(
                $this->getTemplateIndex() . AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getBaseVariables()
                )
            );
        }

        Craft::$app->getSession()->setNotice(Craft::t($this->getSamlPlugin()->getHandle(), 'Provider saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Actions
     */

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionChangeStatus()
    {

        $this->requireAdmin();
        $this->requirePostRequest();

        $providerId = Craft::$app->request->getRequiredBodyParam('identifier');

        /** @var string $recordClass */
        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();

        /** @var ProviderInterface $record */
        $record = $recordClass::find()->where([
            'id' => $providerId,
        ])->one();

        $record->enabled = ! $record->enabled;

        if (! $this->getSamlPlugin()->getProvider()->save($record)) {
            return $this->renderTemplate(
                $this->getTemplateIndex() . AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getBaseVariables()
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $providerId = Craft::$app->request->getRequiredBodyParam('identifier');

        /** @var string $recordClass */
        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();

        /** @var ProviderInterface $record */
        $record = $recordClass::find()->where([
            'id' => $providerId,
        ])->one();

        if (! $this->getSamlPlugin()->getProvider()->delete($record)) {
            return $this->renderTemplate(
                $this->getTemplateIndex() . AbstractEditController::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
                array_merge(
                    [
                        'provider' => $record,
                        'keychain' => $record->keychain ?: new KeyChainRecord(),
                    ],
                    $this->getBaseVariables()
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return ProviderInterface
     * @throws \Exception
     */
    protected function processSaveAction()
    {

        $providerId = Craft::$app->request->getBodyParam('identifier');
        $keyId = Craft::$app->request->getBodyParam('keychain');
        $enabled = Craft::$app->request->getParam('enabled', false) == '1' ? true : false;
        $label = Craft::$app->request->getRequiredParam('label');

        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();
        /** @var ProviderInterface $record */
        if ($providerId) {
            $record = $recordClass::find()->where([
                'id' => $providerId,
            ])->one();

            if (! $record) {
                throw new \Exception("Provider with ID: {$providerId} not found.");
            }

        } else {
            $record = new $recordClass();
            /**
             * enabled is default
             */
            $record->enabled = true;
        }

        $record->label = $label;

        if ($keyId) {
            /** @var KeyChainRecord $keychain */
            if ($keychain = KeyChainRecord::find()->where([
                'id' => $keyId,
            ])->one()) {
                $record->setKeychain(
                    $keychain
                );
            }
        }

        Craft::configure(
            $record,
            [
                'providerType' => Craft::$app->request->getBodyParam('providerType'),
            ]
        );

        return $record;
    }

    /**
     * @param $keyId
     * @return static
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function actionDownloadCertificate($keyId)
    {
        $this->requireAdmin();

        /** @var KeyChainRecord $keychain */
        if (! $keychain = KeyChainRecord::find()->where([
            'id' => $keyId,
        ])->one()) {
            throw new NotFoundHttpException('Key not found');
        }

        return Craft::$app->response->sendContentAsFile($keychain->getDecryptedCertificate(), 'certificate.crt');
    }
}