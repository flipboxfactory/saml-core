<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 10:47 PM
 */

namespace flipbox\saml\core\controllers;


use craft\web\Controller;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\traits\EnsureSamlPlugin;

abstract class AbstractMetadataController extends Controller
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

        if($provider) {
            $metadata = $provider->getMetadataModel();
        }else{
            throw new InvalidMetadata('Metadata for this server is missing. Please configure this plugin.');
        }

        SerializeHelper::xmlContentType();
        return SerializeHelper::toXml($metadata);
    }

}