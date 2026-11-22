<?php

namespace flipbox\saml\core\services;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\events\MetadataCreated;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\helpers\UrlHelper;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use SAML2\Certificate\Key;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\XML\ds\KeyInfo;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\KeyDescriptor;
use SAML2\XML\md\SPSSODescriptor;
use SAML2\XML\md\SSODescriptorType;

/**
 * Class AbstractMetadata
 */
class Metadata extends PluginService
{
    public const SET_SIGNING = Key::USAGE_SIGNING;

    public const SET_ENCRYPTION = Key::USAGE_ENCRYPTION;

    public const PROTOCOL = Constants::NS_SAMLP;

    /**
     * Largest metadata document fetched from a URL.
     */
    public const MAX_FETCH_BYTES = 5 * 1024 * 1024;

    /**
     * @var list<string>
     */
    protected array $supportedBindings = [
        Constants::BINDING_HTTP_POST,
    ];

    /**
     * @return list<string>
     */
    public function getSupportedBindings(): array
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
     * @throws \Exception
     */
    /**
     * Fetches and parses metadata from a URL (admin-supplied). Fails closed on HTTP errors, oversized documents and
     * invalid XML.
     *
     * @throws InvalidMetadata
     */
    public function fetchByUrl(string $url): EntityDescriptor
    {
        if (! preg_match('#^https?://#i', $url)) {
            throw new InvalidMetadata('Metadata URLs must use http or https.');
        }

        $response = Http::timeout(15)->connectTimeout(5)->withOptions(['stream' => true])->get($url);

        if (! $response->successful()) {
            throw new InvalidMetadata(sprintf('Fetching metadata failed with HTTP %d.', $response->status()));
        }

        $body = $response->toPsrResponse()->getBody();
        $xml = '';
        while (! $body->eof()) {
            $xml .= $body->read(65536);
            if (strlen($xml) > static::MAX_FETCH_BYTES) {
                throw new InvalidMetadata('The metadata document is too large.');
            }
        }

        return new EntityDescriptor(DOMDocumentFactory::fromString($xml)->documentElement);
    }

    public function create(
        AbstractSettings $settings,
        AbstractProvider $provider,
    ): EntityDescriptor {
        $entityDescriptor = new EntityDescriptor;

        $entityId = $provider->entityId ?: $settings->getEntityId();

        $entityDescriptor->setEntityID($entityId);

        foreach ($this->getSupportedBindings() as $binding) {
            $entityDescriptor->addRoleDescriptor(
                $descriptor = $this->createDescriptor(
                    $binding,
                    $settings,
                    $provider
                )
            );

            /**
             * Add security settings
             */
            if ($provider->keychain) {
                $this->setEncrypt($descriptor, $provider->keychain);
                $this->setSign($descriptor, $provider->keychain);
            }
        }

        // Listeners may change the metadata before it's published
        event(new MetadataCreated($this->getPlugin(), $entityDescriptor));

        return $entityDescriptor;
    }

    /**
     * @return IDPSSODescriptor|SPSSODescriptor
     *
     * @throws InvalidArgumentException
     */
    protected function createDescriptor(
        string $binding,
        AbstractSettings $settings,
        AbstractProvider $provider,
    ) {
        if (! in_array($binding, [
            Constants::BINDING_HTTP_POST,
            Constants::BINDING_HTTP_REDIRECT,
        ])) {
            throw new InvalidArgumentException('Binding not supported: '.$binding);
        }

        if ($settings->getMyType() === $settings::SP) {
            $descriptor = $this->createSpDescriptor($binding, $settings, $provider);
        } else {
            $descriptor = $this->createIdpDescriptor($binding, $settings, $provider);
        }

        return $descriptor;
    }

    /**
     * @return IDPSSODescriptor
     */
    protected function createIdpDescriptor(
        string $binding,
        AbstractSettings $settings,
        ?AbstractProvider $provider = null,
    ) {
        $descriptor = new IDPSSODescriptor;
        $descriptor->setProtocolSupportEnumeration([
            static::PROTOCOL,
        ]);

        $descriptor->setWantAuthnRequestsSigned(true);

        if (property_exists($settings, 'wantsAuthnRequestsSigned')) {
            $descriptor->setWantAuthnRequestsSigned(
                $settings->wantsAuthnRequestsSigned
            );
        }

        // SSO
        $ssoEndpoint = new EndpointType;
        $ssoEndpoint->setBinding($binding);
        $ssoEndpoint->setLocation(
            UrlHelper::buildEndpointUrl(
                $settings,
                UrlHelper::LOGIN_ENDPOINT,
                $provider
            )
        );

        $descriptor->setSingleSignOnService([
            $ssoEndpoint,
        ]);

        // SLO
        $this->addSloEndpoint(
            $descriptor,
            $settings,
            $provider
        );

        // todo add attributes from mapping

        return $descriptor;
    }

    /**
     * @return SPSSODescriptor
     */
    protected function createSpDescriptor(string $binding, AbstractSettings $settings, AbstractProvider $provider)
    {
        $descriptor = new SPSSODescriptor;
        $descriptor->setProtocolSupportEnumeration([
            static::PROTOCOL,
        ]);

        $descriptor->setAuthnRequestsSigned(true);
        $descriptor->setWantAssertionsSigned(true);

        // ACS
        $acsEndpoint = new IndexedEndpointType;
        $acsEndpoint->setIndex(1);
        $acsEndpoint->setBinding($binding);
        $acsEndpoint->setLocation(
            UrlHelper::buildEndpointUrl(
                $settings,
                UrlHelper::LOGIN_ENDPOINT,
                $provider
            )
        );

        $descriptor->setAssertionConsumerService([
            $acsEndpoint,
        ]);

        // SLO
        $this->addSloEndpoint(
            $descriptor,
            $settings,
            $provider
        );

        // todo add attribute consuming service
        //        var_dump(
        //            $provider->getMapping()
        //        );exit;
        //        $attributeConsumingService = new AttributeConsumingService();
        //        $attributeConsumingService->addRequestedAttribute($att = new RequestedAttribute());
        //        $att->setName('username');
        //        $descriptor->addAttributeConsumingService($attributeConsumingService);

        return $descriptor;
    }

    protected function addSloEndpoint(
        SSODescriptorType $descriptorType,
        AbstractSettings $settings,
        AbstractProvider $provider,
    ): void {
        $sloEndpointRedirect = new EndpointType;
        $sloEndpointRedirect->setBinding(
            Constants::BINDING_HTTP_REDIRECT
        );

        $sloLogoutEndpointUrl = UrlHelper::buildEndpointUrl(
            $settings,
            UrlHelper::LOGOUT_ENDPOINT,
            $provider
        );
        $sloEndpointRedirect->setLocation(
            $sloLogoutEndpointUrl
        );

        $sloEndpointPost = new EndpointType;
        $sloEndpointPost->setBinding(
            Constants::BINDING_HTTP_POST
        );
        $sloEndpointPost->setLocation(
            $sloLogoutEndpointUrl
        );

        $descriptorType->setSingleLogoutService([
            $sloEndpointRedirect,
            $sloEndpointPost,
        ]);
    }

    protected function setCertificate(
        SSODescriptorType $ssoDescriptor,
        KeyChainRecord $keyChainRecord,
        string $signOrEncrypt,
    ): void {
        /**
         * Validate use string
         */
        if (! in_array($signOrEncrypt, [
            self::SET_SIGNING,
            self::SET_ENCRYPTION,
        ])) {
            throw new InvalidArgumentException('Sign or Encrypt argument can only be "signing" or "encrypt"');
        }

        /**
         * Create sub object
         */
        $keyDescriptor = new KeyDescriptor;
        $keyInfo = new KeyInfo;
        $x509Data = new X509Data;
        $x509Certificate = new X509Certificate;

        $keyInfo->addInfo($x509Data);

        $x509Certificate->setCertificate(
            SecurityHelper::cleanCertificate($keyChainRecord->getDecryptedCertificate())
        );

        $x509Data->addData($x509Certificate);
        $keyDescriptor->setKeyInfo($keyInfo);

        $keyDescriptor->setUse($signOrEncrypt);
        $ssoDescriptor->addKeyDescriptor($keyDescriptor);
    }

    public function updateDescriptorCertificates(SSODescriptorType $ssoDescriptor, KeyChainRecord $keyChainRecord): void
    {
        $this->setSign($ssoDescriptor, $keyChainRecord);
        $this->setEncrypt($ssoDescriptor, $keyChainRecord);
    }

    protected function setSign(SSODescriptorType $ssoDescriptor, KeyChainRecord $keyChainRecord): void
    {
        $this->setCertificate($ssoDescriptor, $keyChainRecord, static::SET_SIGNING);
    }

    protected function setEncrypt(SSODescriptorType $ssoDescriptor, KeyChainRecord $keyChainRecord): void
    {
        $this->setCertificate($ssoDescriptor, $keyChainRecord, static::SET_ENCRYPTION);
    }
}
