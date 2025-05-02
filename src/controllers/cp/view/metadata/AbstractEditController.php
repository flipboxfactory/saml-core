<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/10/18
 * Time: 8:40 PM
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;

use Craft;
use craft\helpers\UrlHelper;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\models\SettingsInterface;

/**
 * Class AbstractEditController
 * @package flipbox\saml\core\controllers\cp\view\metadata
 */
abstract class AbstractEditController extends AbstractController implements EnsureSAMLPlugin
{
    use VariablesTrait;

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    /**
     * @param string|null $providerId
     * @return \yii\web\Response
     */
    public function actionIndex($providerId = null, $overwriteVariables = [])
    {
        $variables = $this->getPlugin()->getEditProvider()->prepVariables($providerId);
        /** @var AbstractProvider $provider */
        $provider = $variables['provider'];

        $variables['title'] = Craft::t(
            $this->getPlugin()->getHandle(),
            'Edit ' . $this->getPlugin()->getEditProvider()->getTitle($provider->getType())
        );

        $variables['createType'] = $provider->getType();

        if (isset($variables['provider']) && $variables['provider'] instanceof ProviderInterface) {

            /**
             * Actions
             */
            $variables['formActions'] = $this->getPlugin()->getEditProvider()->getActions($variables['provider']);
        }

        $variables = array_merge($variables, $overwriteVariables);
        return $this->renderTemplate(
            $this->getPlugin()->getEditProvider()->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }

    /**
     * @return \yii\web\Response
     */
    public function actionNewIdp()
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getPlugin();
        $providerRecord = $this->getPlugin()->getProviderRecordClass();
        return $this->actionIndex(null, [
            'title' => 'New ' . $this->getPlugin()->getEditProvider()->getTitle(SettingsInterface::IDP),
            'createType' => SettingsInterface::IDP,
            'provider' => new $providerRecord([
                'providerType' => SettingsInterface::IDP,
            ]),
            'crumbs' => [
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                            ]
                        )
                    ),
                    'label' => $plugin->name,
                ],
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ],
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                                'new-idp',
                            ]
                        )
                    ),
                    'label' => 'New IDP',
                ],
            ],
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionNewSp()
    {
        /** @var AbstractPlugin $plugin */
        $plugin = $this->getPlugin();
        $providerRecord = $this->getPlugin()->getProviderRecordClass();
        return $this->actionIndex(null, [
            'title' => 'New ' . $this->getPlugin()->getEditProvider()->getTitle(SettingsInterface::SP),
            'createType' => SettingsInterface::SP,
            'provider' => new $providerRecord([
                'providerType' => SettingsInterface::SP,
            ]),
            'crumbs' => [
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                            ]
                        )
                    ),
                    'label' => $plugin->name,
                ],
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                            ]
                        )
                    ),
                    'label' => 'Provider List',
                ],
                [
                    'url' => UrlHelper::cpUrl(
                        implode(
                            '/',
                            [
                                $plugin->getHandle(),
                                'metadata',
                                'new-sp',
                            ]
                        )
                    ),
                    'label' => 'New SP',
                ],
            ],
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionMyProvider()
    {
        $provider = $this->getPlugin()->getProvider()->findOwn();
        $variables = $this->getPlugin()->getEditProvider()->prepVariables(
            $provider ? $provider : null
        );

        if ($provider) {
            $variables['provider'] = $provider;

            $variables = array_merge(
                $variables,
                $this->getPlugin()->getEditProvider()->addUrls($provider)
            );
        } else {
            $provider = $variables['provider'];
            $variables['provider']->entityId = $this->getPlugin()->getSettings()->getEntityId();
            $variables['provider']->providerType = $this->getPlugin()->getMyType();
            $variables['provider']->label = 'My Provider';
        }

        /**
         * Actions
         */
        $variables['formActions'] = $this->getPlugin()->getEditProvider()->getActions($provider, true);

        /**
         * Edit Title
         */
        $variables['title'] = Craft::t(
            $this->getPlugin()->getHandle(),
            'My Provider (' . strtoupper($variables['provider']->providerType) . ')'
        );

        $variables['createType'] = $variables['myType'];

        return $this->renderTemplate(
            $this->getPlugin()->getEditProvider()->getTemplateIndex() . static::TEMPLATE_INDEX . DIRECTORY_SEPARATOR . 'edit',
            $variables
        );
    }
}
