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
    public function init()
    {
        parent::init();
        \Craft::$app->view->registerAssetBundle(
            SamlCore::class
        );
    }

    /**
     * @return string
     */
    protected function getTemplateIndex()
    {
        return $this->getPlugin()->getTemplateRootKey();
    }

    /**
     * @return array
     */
    protected function getBaseVariables()
    {
        $variables = [
            'plugin' => $this->getPlugin(),
            'title' => $this->getPlugin()->name,
//            'pluginHandle' => $this->getPlugin()->getHandle(),
            'pluginVariable' => $this->getPlugin()->getPluginVariableHandle(),
            'ownEntityId' => $this->getPlugin()->getSettings()->getEntityId(),
            'settings' => $this->getPlugin()->getSettings(),

            // Set the "Continue Editing" URL
            'continueEditingUrl' => $this->getBaseCpPath(),
            'baseActionPath' => $this->getBaseCpPath(),
            'baseCpPath' => $this->getBaseCpPath(),
            'templateIndex' => $this->getTemplateIndex(),
            'ownProvider' => $ownProvider = $this->getPlugin()->getProvider()->findOwn(),

            'actions' => [],
        ];

        $variables['selectedSubnavItem'] = $this->getSubNavKey();

        /** @var ProviderInterface $ownProvider */
        if ($ownProvider) {
            $variables = array_merge(
                $this->addUrls($ownProvider),
                $variables
            );
        }

        return $variables;
    }

    /**
     * @return null|string
     */
    protected function getSubNavKey()
    {
        $request = \Craft::$app->request;

        $key = null;
        $path = implode(
            '/',
            [
                $request->getSegment(2),
                $request->getSegment(3),
                $request->getSegment(4),
            ]
        );

        if (preg_match('#^/+$#', $path)) {
            $key = 'saml.setup';
        } elseif (preg_match('#metadata/my-provider/#', $path)) {
            $key = 'saml.myProvider';
        } elseif (preg_match('#metadata/+$#', $path)) {
            $key = 'saml.providers';
        } elseif (preg_match('#keychain/+$#', $path)) {
            $key = 'saml.keychain';
        } elseif (preg_match('#settings/+$#', $path)) {
            $key = 'saml.settings';
        }
        return $key;
    }

    /**
     * @return string
     */
    protected function getBaseCpPath(): string
    {
        return $this->getPlugin()->getHandle();
    }

    /**
     * @param ProviderInterface $provider
     * @param array $variables
     * @return array
     */
    protected function addUrls(ProviderInterface $provider)
    {

        $variables = [];
        $variables['assertionConsumerServices'] = null;
        $variables['singleLogoutServices'] = null;
        $variables['singleSignOnServices'] = null;

        if (! $provider->getMetadataModel()) {
            return $variables;
        }

        /** @var AbstractPlugin $plugin */
        $plugin = $this->getPlugin();

        /**
         * Metadata/EntityDescriptor Model
         */
        $entityDescriptor = $provider->getMetadataModel();

        /**
         * Add SP URLs
         */
        if ($provider->getType() === AbstractSettings::SP) {
            foreach ($entityDescriptor->getRoleDescriptor() as $roleDescriptor) {
                if (! ($roleDescriptor instanceof SPSSODescriptor)) {
                    continue;
                }

                if ($endpoint = $this->getFirstEndpoint($roleDescriptor->getSingleLogoutService())) {
                    $sloBinding = $endpoint->getBinding();
                    $sloResponseLocation = $endpoint->getLocation();
                    $variables['singleLogoutServices'][$sloBinding] = $sloResponseLocation;
                }

                /** @var IndexedEndpointType $firstACS */
                $firstACS = $this->getFirstEndpoint($roleDescriptor->getAssertionConsumerService());
                $acsBinding = $firstACS->getBinding();
                $acsLocation = $firstACS->getLocation();
                $variables['assertionConsumerServices'][$acsBinding] = $acsLocation;
            }
        }

        /**
         * Add IDP URLs
         */
        if ($provider->getType() === AbstractSettings::IDP) {
            foreach ($entityDescriptor->getRoleDescriptor() as $roleDescriptor) {
                if (! ($roleDescriptor instanceof IDPSSODescriptor)) {
                    continue;
                }

                if ($endpoint = $this->getFirstEndpoint($roleDescriptor->getSingleLogoutService())) {
                    $sloBinding = $endpoint->getBinding();
                    $sloResponseLocation = $endpoint->getLocation();
                    $variables['singleLogoutServices'][$sloBinding] = $sloResponseLocation;
                }

                $sso = $this->getFirstEndpoint($roleDescriptor->getSingleSignOnService());
                $ssoBinding = $sso->getBinding();
                $ssoLocation = $sso->getLocation();
                $variables['singleSignOnServices'][$ssoBinding] = $ssoLocation;
            }
        }

        return $variables;
    }

    /**
     * @param $endpoints
     * @return EndpointType|null
     */
    protected function getFirstEndpoint($endpoints)
    {
        if (is_null($endpoints) || empty($endpoints)) {
            return null;
        }

        return array_shift($endpoints);
    }


    /**
     * @param $type
     * @return string
     */
    protected function getTitle($type)
    {
        return $type === AbstractSettings::SP ? 'Service Provider (SP)' : 'Identity Provider (IDP)';
    }
}
