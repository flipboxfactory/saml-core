<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers\cp\view;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\AbstractController as BaseController;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\web\assets\bundles\SamlCore;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\SPSSODescriptor;

/**
 * Class AbstractController
 * @package flipbox\saml\core\controllers\cp\view
 */
abstract class AbstractController extends BaseController implements EnsureSAMLPlugin
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp';

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        \Craft::$app->view->registerAssetBundle(
            SamlCore::class
        );
    }
}
