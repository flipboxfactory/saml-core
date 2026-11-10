<?php


namespace flipbox\saml\core\services;

use craft\base\Component;
use craft\helpers\UrlHelper;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\ProviderInterface;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\SPSSODescriptor;

class EditProvider extends Component
{
    /**
     * @var AbstractPlugin
     */
    private $plugin;

    /**
     * @param AbstractPlugin $plugin
     * @return $this
     */
    public function setPlugin(AbstractPlugin $plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * @return AbstractPlugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @param ProviderInterface $provider
     * @return array
     */
    public function getActions(ProviderInterface $provider, bool $isOwn = false)
    {
        $actions = [];

        if ($provider->id) {
            $continueUrl = $this->getPlugin()->getHandle() . '/metadata/' . ($isOwn ? 'my-provider' : $provider->id);
            $actions = [
                [
                    'label' => 'Save and continue editing',
                    'redirect' => \Craft::$app->getSecurity()->hashData($continueUrl, null),
                    'shortcut' => true,
                ],
                [
                    'action' => $this->getPlugin()->getHandle() . '/metadata/change-status',
                    'label' => $provider->enabled ? 'Disable' : 'Enable',
                ],
                [
                    'action' => $this->getPlugin()->getHandle() . '/metadata/delete',
                    'label' => 'Delete',
                    'destructive' => true,
                ],
            ];
        }
        return $actions;
    }

    /**
     * @return array
     */
    public function getBaseVariables()
    {

        return array_merge(
            $this->getVariables(),
            [
                'autoCreate' => false,
                'myEntityId' => $this->getPlugin()->getSettings()->getEntityId(),
                'myType' => $this->getPlugin()->getMyType(),
            ]
        );
    }

    /**
     * @param string|null|ProviderInterface $provider
     * @return array
     */
    public function prepVariables($provider = null)
    {
        $variables = $this->getBaseVariables();

        $variables['title'] = \Craft::t(
            $this->getPlugin()->getHandle(),
            $this->getPlugin()->name
        );


        /**
         * TYPES
         */
        $variables['myType'] = $this->getPlugin()->getMyType();
        $variables['remoteType'] = $this->getPlugin()->getRemoteType();
        $variables['createType'] = $variables['remoteType'];

        if ($provider) {
            /**
             * @var ProviderInterface $provider
             */
            $provider = $variables['provider'] = (
                /**
                 * Is instance provider
                 */
            $provider instanceof ProviderInterface ?
                $provider :
                $provider = $this->getPlugin()->getProviderRecordClass()::find()->where([
                    /**
                     * Is ID
                     */
                    'id' => $provider,
                ])->one()
            );

            $variables['title'] .= ': Edit';

            $crumb = [
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $this->getPlugin()->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ], [
                    'url' => UrlHelper::cpUrl(
                        $this->getPlugin()->getHandle() . '/metadata/' . $provider->id
                    ),
                    'label' => $provider->label ?: $provider->entityId,
                ],
            ];
            $variables['keypair'] = $provider->keychain;

            $variables = array_merge(
                $variables,
                $this->addUrls($provider)
            );
        } else {
            $record = $this->getPlugin()->getProviderRecordClass();

            $provider = $variables['provider'] = new $record([
                'providerType' => SettingsInterface::IDP,
            ]);

            $variables['title'] .= ': Create';

            $crumb = [
                [
                    'url' => UrlHelper::cpUrl(
                        $this->getPlugin()->getHandle() . '/new'
                    ),
                    'label' => 'New',
                ],
            ];
        }

        $variables['allkeypairs'] = [];

        $keypairs = KeyChain::getInstance()->getService()->findByPlugin($this->getPlugin())->all();

        foreach ($keypairs as $keypair) {
            $variables['allkeypairs'][] = [
                'label' => $keypair->description ?: "(Untitled: $keypair->id)",
                'value' => $keypair->id,
            ];
        }

        $variables['crumbs'] = array_merge([
            [
                'url' => UrlHelper::cpUrl($this->getPlugin()->getHandle()),
                'label' => \Craft::t($this->getPlugin()->getHandle(), $this->getPlugin()->name),
            ],
        ], $crumb);

        return $variables;
    }

    protected function getCraftMappingOptions()
    {
    }

    /**
     * @return string
     */
    public function getTemplateIndex()
    {
        return $this->getPlugin()->getTemplateRootKey();
    }

    /**
     * @return array
     */
    protected function getVariables()
    {
        $variables = [
            'plugin' => $this->getPlugin(),
            'title' => $this->getPlugin()->name,
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
    public function addUrls(ProviderInterface $provider)
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
    public function getTitle($type)
    {
        return $type === AbstractSettings::SP ? 'Service Provider (SP)' : 'Identity Provider (IDP)';
    }
}
