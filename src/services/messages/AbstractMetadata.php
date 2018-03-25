<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/9/18
 * Time: 9:48 AM
 */

namespace flipbox\saml\core\services\messages;


use craft\base\Component;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\services\traits\Metadata as MetadataTrait;
use craft\helpers\UrlHelper;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use yii\base\Event;
use yii\base\InvalidConfigException;

abstract class AbstractMetadata extends Component implements MetadataServiceInterface
{
    use MetadataTrait;

    const EVENT_SAML_MODEL_CREATED = 'eventSamlModelCreated';

    const LOGOUT_RESPONSE_LOCATION = '';
    const LOGOUT_REQUEST_LOCATION = '';
    const LOGIN_LOCATION = '';

    /**
     * @return string
     */
    public static function getLogoutResponseLocation()
    {
        return UrlHelper::actionUrl(static::LOGOUT_RESPONSE_LOCATION);
    }

    /**
     * @return string
     */
    public static function getLogoutRequestLocation()
    {
        return UrlHelper::actionUrl(static::LOGOUT_REQUEST_LOCATION);
    }

    /**
     * @return string
     */
    public static function getLoginLocation()
    {
        return UrlHelper::actionUrl(static::LOGIN_LOCATION);
    }

    /**
     * @param ProviderInterface $provider
     * @param KeyChainRecord $keychain
     */
    public function updateKeychain(ProviderInterface $provider, KeyChainRecord $keychain)
    {
        foreach ($provider->getMetadataModel()->getAllEndpoints() as $endpoint) {
            if ($this->useEncryption($provider)) {
                $this->setEncrypt($endpoint->getDescriptor(), $keychain);
            }

            if ($this->useSigning($provider)) {
                $this->setSign($endpoint->getDescriptor(), $keychain);
            }

        }

        $provider->setKeychain($keychain);
    }

    /**
     * @param string $binding
     * @return IdpSsoDescriptor|SpSsoDescriptor
     * @throws InvalidConfigException
     */
    protected function createDescriptor(string $binding)
    {
        if (! in_array($binding, [
            SamlConstants::BINDING_SAML2_HTTP_REDIRECT,
            SamlConstants::BINDING_SAML2_HTTP_POST,
        ])) {
            throw new InvalidConfigException('Binding not supported: ' . $binding);
        }

        /** @var AbstractPlugin $plugin */
        $plugin = $this->getSamlPlugin();

        /** @var AbstractMetadata $metadata */
        $metadata = $plugin->getMetadata();
        if ($plugin->getMyType() === $plugin::SP) {
            $descriptor = $this->createSpDescriptor($binding);
        } else {
            $descriptor = $this->createIdpDescriptor($binding);
        }


        return $descriptor;
    }

    /**
     * @param string $binding
     * @return IdpSsoDescriptor
     */
    protected function createIdpDescriptor(string $binding)
    {
        $descriptor = new IdpSsoDescriptor();

        $descriptor->setWantAuthnRequestsSigned(
            $this->getSamlPlugin()->getSettings()->signAuthnRequest
        );

        $singleLogout = new SingleLogoutService();
        $singleLogout->setLocation(static::getLogoutRequestLocation())
            ->setResponseLocation(static::getLogoutResponseLocation())
            ->setBinding($binding);
        $descriptor->addSingleSignOnService(
            new SingleSignOnService(
                static::getLoginLocation(),
                $binding
            )
        )->addSingleLogoutService($singleLogout);

        return $descriptor;
    }

    /**
     * @param string $binding
     * @return SpSsoDescriptor
     */
    protected function createSpDescriptor(string $binding)
    {
        $descriptor = new SpSsoDescriptor();
        $descriptor->setWantAssertionsSigned($this->getSamlPlugin()->getSettings()->signAssertions);

        //ASC
        $acs = new AssertionConsumerService();
        $acs->setBinding($binding)
            ->setLocation(static::getLoginLocation());

        //SLO
        $singleLogout = new SingleLogoutService();
        $singleLogout->setLocation(static::getLogoutRequestLocation())
            ->setResponseLocation(static::getLogoutResponseLocation())
            ->setBinding($binding);

        $descriptor
            ->addAssertionConsumerService($acs)
            ->addSingleLogoutService(
                $singleLogout
            );

        return $descriptor;
    }

    /**
     * @param KeyChainRecord|null $withKeyPair
     * @return ProviderInterface
     * @throws InvalidConfigException
     */
    public function create(KeyChainRecord $withKeyPair = null): ProviderInterface
    {


        $entityDescriptor = new EntityDescriptor(
            $this->getSamlPlugin()->getSettings()->getEntityId()
        );

        foreach ($this->getSupportedBindings() as $binding) {
            $entityDescriptor->addItem(
                $this->createDescriptor($binding)
            );
        }

        $recordClass = $this->getSamlPlugin()->getProvider()->getRecordClass();

        /** @var ProviderInterface $provider */
        $provider = (new $recordClass())
            ->loadDefaultValues();

        $provider->providerType = 'sp';

        \Craft::configure($provider, [
            'entityId' => $entityDescriptor->getEntityID(),
            'metadata' => SerializeHelper::toXml($entityDescriptor),
        ]);

        $this->updateKeychain(
            $provider,
            $withKeyPair
        );

        /**
         * After event for Metadata creation
         */
        $event = new Event();
        $event->data = $entityDescriptor;

        $this->trigger(
            static::EVENT_SAML_MODEL_CREATED,
            $event
        );

        return $provider;
    }
}
