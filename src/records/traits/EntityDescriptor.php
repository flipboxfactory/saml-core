<?php

declare(strict_types=1);

namespace flipbox\saml\core\records\traits;

use flipbox\saml\core\helpers\EntityDescriptorHelper;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\AbstractProvider;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Certificate\Key;
use SAML2\Utilities\Certificate;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\KeyDescriptor;
use SAML2\XML\md\SPSSODescriptor;

/**
 * Endpoints and keys from a provider's metadata.
 *
 * @mixin AbstractProvider
 */
trait EntityDescriptor
{
    /**
     * @var list<IDPSSODescriptor>
     */
    private array $idpSsoDescriptors = [];

    /**
     * @var list<SPSSODescriptor>
     */
    private array $spSsoDescriptors = [];

    /**
     * @return list<IDPSSODescriptor>
     */
    public function idpSsoDescriptors(): array
    {
        if (! $this->idpSsoDescriptors && $model = $this->getMetadataModel()) {
            /** @var list<IDPSSODescriptor> $descriptors */
            $descriptors = EntityDescriptorHelper::getIdpDescriptors($model);
            $this->idpSsoDescriptors = $descriptors;
        }

        return $this->idpSsoDescriptors;
    }

    /**
     * @return list<SPSSODescriptor>
     */
    public function spSsoDescriptors(): array
    {
        if (! $this->spSsoDescriptors && $model = $this->getMetadataModel()) {
            /** @var list<SPSSODescriptor> $descriptors */
            $descriptors = EntityDescriptorHelper::getSpDescriptors($model);
            $this->spSsoDescriptors = $descriptors;
        }

        return $this->spSsoDescriptors;
    }

    public function firstSpAcsService(?string $binding = null): ?EndpointType
    {
        return EntityDescriptorHelper::getFirstSpAssertionConsumerService($this->spSsoDescriptors(), $binding);
    }

    public function firstIdpSsoService(?string $binding = null): ?EndpointType
    {
        return EntityDescriptorHelper::getFirstIdpSSOService($this->idpSsoDescriptors(), $binding);
    }

    public function firstIdpSloService(?string $binding = null): ?EndpointType
    {
        return EntityDescriptorHelper::getFirstSLOService($this->idpSsoDescriptors(), $binding);
    }

    public function firstSpSloService(?string $binding = null): ?EndpointType
    {
        return EntityDescriptorHelper::getFirstSLOService($this->spSsoDescriptors(), $binding);
    }

    /**
     * The first signing certificate in the metadata, as a public key, or null if there isn't one.
     */
    public function signingXMLSecurityKey(): ?XMLSecurityKey
    {
        $certificate = $this->firstCertificate(Key::USAGE_SIGNING);

        return $certificate ? $this->certificateToXMLSecurityKey($certificate) : null;
    }

    /**
     * Every signing certificate in the metadata, as public keys (empty if there are none).
     *
     * @return list<XMLSecurityKey>
     */
    public function signingXMLSecurityKeyStore(): array
    {
        $store = [];
        foreach ($this->keyDescriptorsByType($this->keyDescriptors(), Key::USAGE_SIGNING) as $keyDescriptor) {
            foreach ($this->x509Certificates($keyDescriptor) as $certificate) {
                $store[] = $this->certificateToXMLSecurityKey($certificate);
            }
        }

        return $store;
    }

    /**
     * The first encryption certificate in the metadata, as an RSA-OAEP public key, or null if there isn't one.
     */
    public function encryptionKey(): ?XMLSecurityKey
    {
        if (! $certificate = $this->firstCertificate(Key::USAGE_ENCRYPTION)) {
            return null;
        }

        $xmlSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
        $xmlSecurityKey->loadKey($this->certificatePem($certificate));

        return $xmlSecurityKey;
    }

    protected function certificateToXMLSecurityKey(X509Certificate $certificate): XMLSecurityKey
    {
        $xmlSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $xmlSecurityKey->loadKey($this->certificatePem($certificate), false, true);

        return $xmlSecurityKey;
    }

    protected function certificatePem(X509Certificate $certificate): string
    {
        return Certificate::convertToCertificate(SecurityHelper::cleanCertificateWhiteSpace($certificate->getCertificate()));
    }

    protected function firstCertificate(string $use): ?X509Certificate
    {
        foreach ($this->keyDescriptorsByType($this->keyDescriptors(), $use) as $keyDescriptor) {
            if ($certificates = $this->x509Certificates($keyDescriptor)) {
                return $certificates[0];
            }
        }

        return null;
    }

    /**
     * The key descriptors of the provider's first SSO descriptor of its type.
     *
     * @return list<KeyDescriptor>
     */
    private function keyDescriptors(): array
    {
        $descriptors = $this->isIdentityProvider() ? $this->idpSsoDescriptors() : $this->spSsoDescriptors();

        return isset($descriptors[0]) ? array_values($descriptors[0]->getKeyDescriptor()) : [];
    }

    /**
     * Key descriptors for a use. One without a `use` attribute is for both signing and encryption (SAML metadata
     * 2.4.1.1).
     *
     * @param  list<KeyDescriptor>  $keyDescriptors
     * @return list<KeyDescriptor>
     */
    private function keyDescriptorsByType(array $keyDescriptors, string $use): array
    {
        return array_values(array_filter(
            $keyDescriptors,
            fn (KeyDescriptor $keyDescriptor) => in_array($keyDescriptor->getUse(), [$use, null, ''], true),
        ));
    }

    /**
     * The X.509 certificates in a key descriptor, from every X509Data element, in document order.
     *
     * @return list<X509Certificate>
     */
    private function x509Certificates(KeyDescriptor $keyDescriptor): array
    {
        $certificates = [];
        foreach ($keyDescriptor->getKeyInfo()->getInfo() as $info) {
            if (! $info instanceof X509Data) {
                continue;
            }
            foreach ($info->getData() as $datum) {
                if ($datum instanceof X509Certificate) {
                    $certificates[] = $datum;
                }
            }
        }

        return $certificates;
    }

    public function toXmlString(): string
    {
        return (string) $this->getMetadataModel()?->toXML()->ownerDocument?->saveXML();
    }

    public function __toString(): string
    {
        return $this->toXmlString();
    }
}
