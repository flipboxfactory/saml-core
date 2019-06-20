<?php

namespace flipbox\saml\core\services;

use craft\base\Component;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\models\SettingsInterface;
use SAML2\Certificate\Key;
use SAML2\Constants;
use SAML2\XML\ds\KeyInfo;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\KeyDescriptor;
use SAML2\XML\md\SPSSODescriptor;
use SAML2\XML\md\SSODescriptorType;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * Class AbstractMetadata
 * @package flipbox\saml\core\services\messages
 */
class Metadata extends Component
{

    const SET_SIGNING = Key::USAGE_SIGNING;
    const SET_ENCRYPTION = Key::USAGE_ENCRYPTION;

    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @var array
     */
    protected $supportedBindings = [
        Constants::BINDING_HTTP_POST,
    ];

    /**
     * @return array
     */
    public function getSupportedBindings()
    {
        return $this->supportedBindings;
    }

    /**
     * @return bool
     */
    protected function supportsRedirect()
    {
        return in_array(Constants::BINDING_HTTP_REDIRECT, $this->getSupportedBindings());
    }

    /**
     * @return bool
     */
    protected function supportsPost()
    {
        return in_array(Constants::BINDING_HTTP_POST, $this->getSupportedBindings());
    }

    /**
     * @param SettingsInterface $settings
     * @param KeyChainRecord|null $withKeyPair
     * @return EntityDescriptor
     * @throws InvalidConfigException
     */
    public function create(
        SettingsInterface $settings,
        KeyChainRecord $withKeyPair = null
    ): EntityDescriptor {

        $entityDescriptor = new EntityDescriptor();

        $entityId = $settings->getEntityId();

        $entityDescriptor->setEntityID($entityId);

        foreach ($this->getSupportedBindings() as $binding) {
            $entityDescriptor->addRoleDescriptor(
                $descriptor = $this->createDescriptor($binding, $settings)
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

    /**
     * @param string $binding
     * @return IdpSsoDescriptor|SpSsoDescriptor
     * @throws InvalidConfigException
     */
    protected function createDescriptor(string $binding, SettingsInterface $settings)
    {
        if (! in_array($binding, [
            Constants::BINDING_HTTP_POST,
            Constants::BINDING_HTTP_REDIRECT,
        ])) {
            throw new InvalidConfigException('Binding not supported: ' . $binding);
        }

        if ($settings->getMyType() === $settings::SP) {
            $descriptor = $this->createSpDescriptor($binding, $settings);
        } else {
            $descriptor = $this->createIdpDescriptor($binding, $settings);
        }


        return $descriptor;
    }

    /**
     * @param string $binding
     * @return IDPSSODescriptor
     */
    protected function createIdpDescriptor(string $binding, SettingsInterface $settings)
    {
        $descriptor = new \SAML2\XML\md\IDPSSODescriptor();

        // SSO
        $ssoEndpoint = new IndexedEndpointType();
        $ssoEndpoint->setIndex(1);
        $ssoEndpoint->setBinding($binding);
        $ssoEndpoint->setLocation(
            $settings->getDefaultLoginEndpoint()
        );
        $descriptor->setSingleSignOnService([
            $ssoEndpoint,
        ]);

        // SLO
        $sloEndpoint = new IndexedEndpointType();
        $sloEndpoint->setIndex(1);
        $sloEndpoint->setBinding($binding);
        $sloEndpoint->setLocation(
            $settings->getDefaultLogoutEndpoint()
        );

        $descriptor->setSingleLogoutService([
            $sloEndpoint,
        ]);


        return $descriptor;
    }

    /**
     * @param string $binding
     * @return SPSSODescriptor
     */
    protected function createSpDescriptor(string $binding, SettingsInterface $settings)
    {

        $descriptor = new SPSSODescriptor();

        if (property_exists($settings, 'wantsSignedAssertions') &&
            is_bool($settings->wantsSignedAssertions)
        ) {
            $descriptor->setWantAssertionsSigned(
                $settings->wantsSignedAssertions
            );
        }


        // ACS
        $acsEndpoint = new IndexedEndpointType();
        $acsEndpoint->setIndex(1);
        $acsEndpoint->setBinding($binding);
        $acsEndpoint->setLocation(
            $settings->getDefaultLoginEndpoint()
        );

        $descriptor->setAssertionConsumerService([
            $acsEndpoint,
        ]);

        //SLO
        $sloEndpoint = new IndexedEndpointType();
        $sloEndpoint->setIndex(1);
        $sloEndpoint->setBinding($binding);
        $sloEndpoint->setLocation(
            $settings->getDefaultLogoutEndpoint()
        );
        $descriptor->setSingleLogoutService([
            $sloEndpoint,
        ]);


        return $descriptor;
    }

    /**
     * @param SSODescriptorType $ssoDescriptor
     * @param KeyChainRecord $keyChainRecord
     */
    protected function setCertificate(
        SSODescriptorType $ssoDescriptor,
        KeyChainRecord $keyChainRecord,
        string $signOrEncrypt
    ) {
        /**
         * Validate use string
         */
        if (! in_array($signOrEncrypt, [
            self::SET_SIGNING,
            self::SET_ENCRYPTION,
        ])) {
            throw new \InvalidArgumentException('Sign or Encrypt argument can only be "signing" or "encrypt"');
        }

        /**
         * Create sub object
         */
        $keyDescriptor = new KeyDescriptor();
        $keyInfo = new KeyInfo();
        $x509Data = new X509Data();
        $x509Certificate = new X509Certificate();

        $keyInfo->addInfo($x509Data);

        $x509Certificate->setCertificate(
            SecurityHelper::cleanCertificate($keyChainRecord->getDecryptedCertificate())
        );

        $x509Data->addData($x509Certificate);
        $keyDescriptor->setKeyInfo($keyInfo);

        $keyDescriptor->setUse($signOrEncrypt);
        $ssoDescriptor->addKeyDescriptor($keyDescriptor);
    }

    /**
     * @param SSODescriptorType
     * @param KeyChainRecord $keyChainRecord
     */
    protected function setSign(SSODescriptorType $ssoDescriptor, KeyChainRecord $keyChainRecord)
    {
        $this->setCertificate($ssoDescriptor, $keyChainRecord, static::SET_SIGNING);
    }

    /**
     * @param SSODescriptorType
     * @param KeyChainRecord $keyChainRecord
     */
    protected function setEncrypt(SSODescriptorType $ssoDescriptor, KeyChainRecord $keyChainRecord)
    {
        $this->setCertificate($ssoDescriptor, $keyChainRecord, static::SET_ENCRYPTION);
    }
}
