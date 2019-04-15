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
use flipbox\saml\core\services\traits\Metadata as MetadataTrait;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use SAML2\Configuration\IdentityProvider;
use SAML2\Constants;
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

    /**
     * @param string $binding
     * @return IdpSsoDescriptor|SpSsoDescriptor
     * @throws InvalidConfigException
     */
    protected function createDescriptor(string $binding)
    {
        if (! in_array($binding, [
            Constants::BINDING_HTTP_POST,
            Constants::BINDING_HTTP_REDIRECT,
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
        $IdPDescriptor = new \SAML2\XML\md\IDPSSODescriptor();
        $IdPDescriptor->setSingleSignOnService([
            'Location' => ,
                'ResponseLocation'=>,
        ]);
        $descriptor = new IdpSsoDescriptor();

        $singleLogout = new SingleLogoutService();
        $singleLogout->setLocation($this->getSamlPlugin()->getSettings()->getDefaultLogoutRequestEndpoint())
            ->setResponseLocation($this->getSamlPlugin()->getSettings()->getDefaultLogoutEndpoint())
            ->setBinding($binding);
        $descriptor->addSingleSignOnService(
            new SingleSignOnService(
                $this->getSamlPlugin()->getSettings()->getDefaultLoginEndpoint(),
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
        if (property_exists($this->getSamlPlugin()->getSettings(), 'wantsSignedAssertions') &&
            is_bool($this->getSamlPlugin()->getSettings()->wantsSignedAssertions)
        ) {
            $descriptor->setWantAssertionsSigned($this->getSamlPlugin()->getSettings()->wantsSignedAssertions);
        }

        //ASC
        $acs = new AssertionConsumerService();
        $acs->setBinding($binding)
            ->setLocation($this->getSamlPlugin()->getSettings()->getDefaultLoginEndpoint());

        //SLO
        $singleLogout = new SingleLogoutService();
        $singleLogout->setLocation($this->getSamlPlugin()->getSettings()->getDefaultLogoutRequestEndpoint())
            ->setResponseLocation($this->getSamlPlugin()->getSettings()->getDefaultLogoutEndpoint())
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
     * @param null $entityId
     * @return EntityDescriptor
     * @throws InvalidConfigException
     */
    public function create(KeyChainRecord $withKeyPair = null, $entityId = null): EntityDescriptor
    {

        $entityDescriptor = new EntityDescriptor(
            $entityId ?: $this->getSamlPlugin()->getSettings()->getEntityId()
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
