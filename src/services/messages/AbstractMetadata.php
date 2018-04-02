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
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\services\traits\Metadata as MetadataTrait;
use craft\helpers\UrlHelper;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Metadata\SSODescriptor;
use LightSaml\SamlConstants;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * Class AbstractMetadata
 * @package flipbox\saml\core\services\messages
 */
abstract class AbstractMetadata extends Component implements MetadataServiceInterface
{
    use MetadataTrait, EnsureSamlPlugin;

    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

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
     * @return EntityDescriptor
     * @throws InvalidConfigException
     */
    public function create(KeyChainRecord $withKeyPair = null): EntityDescriptor
    {

        $entityDescriptor = new EntityDescriptor(
            $this->getSamlPlugin()->getSettings()->getEntityId()
        );

        foreach ($this->getSupportedBindings() as $binding) {

            $entityDescriptor->addItem(
                $descriptor = $this->createDescriptor($binding)
            );

            /**
             * Add security settings
             */
            if ($withKeyPair) {
                $this->setEncrypt($descriptor, $withKeyPair);
                $this->setSign($descriptor, $withKeyPair);
            }
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        $event = new Event();
        /**
         * response
         */
        $event->data = $entityDescriptor;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $entityDescriptor;
    }
}
