<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 10:47 PM
 */

namespace flipbox\saml\core\controllers;


use Craft;
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
        );

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

        $record = $this->processSaveAction();

        $entityDescriptor = $this->getSamlPlugin()->getMetadata()->create(
            $record->keychain
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

        Craft::$app->getSession()->setNotice(Craft::t($this->getSamlPlugin()->getUniqueId(), 'Provider saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return ProviderInterface
     * @throws \yii\web\BadRequestHttpException
     */
    protected function processSaveAction()
    {
        $this->requirePostRequest();
        $providerId = Craft::$app->request->getBodyParam('identifier');
        $keyId = Craft::$app->request->getBodyParam('keychain');
        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();
        /** @var ProviderInterface $record */
        if ($providerId) {
            $record = $recordClass::find()->where([
                'id' => $providerId,
            ])->one();
        } else {
            $record = new $recordClass();
        }

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
                'enabled'      => Craft::$app->request->getBodyParam('enabled') ?: false,
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