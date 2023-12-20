<?php


namespace flipbox\saml\core\records\traits;

use flipbox\saml\core\helpers\EntityDescriptorHelper;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\AbstractProvider;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Certificate\Key;
use SAML2\Utilities\Certificate;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\md\EntityDescriptor as SAML2EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\KeyDescriptor;
use SAML2\XML\md\SPSSODescriptor;

/**
 * Trait EntityDescriptor
 * @package flipbox\saml\core\records\traits
 * @method SAML2EntityDescriptor getMetadataModel
 * @mixin AbstractProvider
 */
trait EntityDescriptor
{

    /**
     * IDP descriptors from the metadata
     * @see AbstractProvider::getMetadataModel()
     * @var IDPSSODescriptor[]
     */
    private $idpSsoDescriptors = [];

    /**
     * SP descriptors from the metadata
     * @see AbstractProvider::getMetadataModel()
     * @var SPSSODescriptor[]
     */
    private $spSsoDescriptors = [];

    /**
     * Get the role descriptors from metadata
     * @return IDPSSODescriptor[]
     */
    public function idpSsoDescriptors()
    {
        if (! $this->idpSsoDescriptors) {
            $this->idpSsoDescriptors = EntityDescriptorHelper::getIdpDescriptors($this->getMetadataModel());
        }

        return $this->idpSsoDescriptors;
    }

    /**
     * Get the role descriptors from metadata
     * @return SPSSODescriptor[]
     */
    public function spSsoDescriptors()
    {
        if (! $this->spSsoDescriptors) {
            $this->spSsoDescriptors = EntityDescriptorHelper::getSpDescriptors($this->getMetadataModel());
        }

        return $this->spSsoDescriptors;
    }

    /**
     * @param null $binding
     * @return IndexedEndpointType|null
     */
    public function firstSpAcsService($binding = null)
    {
        return EntityDescriptorHelper::getFirstSpAssertionConsumerService($this->spSsoDescriptors(), $binding);
    }

    /** SSO */

    /**
     * @param null $binding
     * @return IndexedEndpointType|null
     */
    public function firstIdpSsoService($binding = null)
    {
        return EntityDescriptorHelper::getFirstIdpSSOService($this->idpSsoDescriptors(), $binding);
    }

    /** SLO */

    /**
     * @param null $binding
     * @return IndexedEndpointType|null
     */
    public function firstIdpSloService($binding = null)
    {
        return EntityDescriptorHelper::getFirstSLOService($this->idpSsoDescriptors(), $binding);
    }

    /**
     * @param null $binding
     * @return IndexedEndpointType|null
     */
    public function firstSpSloService($binding = null)
    {
        return EntityDescriptorHelper::getFirstSLOService($this->spSsoDescriptors(), $binding);
    }

    /** X509s */

    /**
     * @return XMLSecurityKey
     * @throws \Exception
     */
    public function signingXMLSecurityKey()
    {
        if (! $certificate = $this->firstCertificateForSigning()) {
            return null;
        }

        return $this->certificateToXMLSecurityKey($certificate);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function signingXMLSecurityKeyStore()
    {

        $store = [];
        if (! ($keyDescriptors = $this->keyDescriptors())) {
            return $store;
        }

        $keyDescriptors = $this->keyDescriptorsByType($keyDescriptors, Key::USAGE_SIGNING);

        foreach ($keyDescriptors as $keyDescriptor) {
            $x509Certificates = $this->x509Certificates($keyDescriptor);
            $store = array_merge(
                $store,
                $this->certificatesToXMLSecurityKeyStore($x509Certificates)
            );
        }

        return $store;
    }

    /**
     * @return XMLSecurityKey|null
     * @throws \Exception
     *
     * For Decryption
     * @see SecurityHelper::decryptAssertion()
     */
    public function encryptionKey()
    {
        if (! $certificate = $this->firstCertificateForEncryption()) {
            return null;
        }

        $pem = Certificate::convertToCertificate(
            SecurityHelper::cleanCertificateWhiteSpace(
                $certificate->getCertificate()
            )
        );

        $xmlSecurityKey = new XMLSecurityKey(
            XMLSecurityKey::RSA_OAEP_MGF1P,
            [
                'type' => 'public',
            ]
        );

        $xmlSecurityKey->loadKey($pem);

        return $xmlSecurityKey;
    }

    /**
     * @param X509Certificate $certificate
     * @return XMLSecurityKey
     * @throws \Exception
     */
    protected function certificateToXMLSecurityKey(X509Certificate $certificate)
    {
        $pem = Certificate::convertToCertificate(
            SecurityHelper::cleanCertificateWhiteSpace(
                $certificate->getCertificate()
            )
        );

        $xmlSecurityKey = new XMLSecurityKey(
            XMLSecurityKey::RSA_SHA256,
            [
                'type' => 'public',
            ]
        );

        $xmlSecurityKey->loadKey($pem, false, true);

        return $xmlSecurityKey;
    }

    /**
     * @param array $certificates
     * @return array
     * @throws \Exception
     */
    protected function certificatesToXMLSecurityKeyStore(array $certificates)
    {
        $store = [];
        foreach ($certificates as $certificate) {
            $store[] = $this->certificateToXMLSecurityKey($certificate);
        }
        return $store;
    }

    /**
     * @param array $keyDescriptors
     * @param string $signingOrEncrypt
     * @return KeyDescriptor|null
     */
    protected function firstKeyDescriptor(array $keyDescriptors, string $signingOrEncrypt)
    {
        /** @var KeyDescriptor[] $keyDescriptorFiltered */
        $keyDescriptorFiltered = $this->keyDescriptorsByType($keyDescriptors, $signingOrEncrypt);

        return count($keyDescriptorFiltered) > 0 ? array_shift($keyDescriptorFiltered) : null;
    }

    /**
     * @return X509Certificate|null
     */
    protected function firstCertificateForSigning()
    {
        return $this->firstCertificate(Key::USAGE_SIGNING);
    }

    /**
     * @return X509Certificate|null
     */
    protected function firstCertificateForEncryption()
    {
        return $this->firstCertificate(Key::USAGE_ENCRYPTION);
    }

    /**
     * @return X509Certificate|null
     */
    protected function firstCertificate($use)
    {

        if (! ($keyDescriptors = $this->keyDescriptors())) {
            return null;
        }

        /** @var KeyDescriptor $keyDescriptor */
        $keyDescriptor = $this->firstKeyDescriptor($keyDescriptors, $use);

        if (is_null($keyDescriptor)) {
            return null;
        }

        $certificates = $this->x509Certificates($keyDescriptor);
        return ! empty($certificates) ? $certificates[0] : null;
    }

    /**
     * @return KeyDescriptor[]
     */
    private function keyDescriptors()
    {

        if (empty($this->spSsoDescriptors()) && empty($this->idpSsoDescriptors())) {
            return null;
        }

        if ($this->isIdentityProvider()) {
            /** @var IDPSSODescriptor $ssoDescriptor */
            $ssoDescriptor = $this->idpSsoDescriptors()[0];
        } else {
            /** @var SPSSODescriptor $ssoDescriptor */
            $ssoDescriptor = $this->spSsoDescriptors()[0];
        }

        return $ssoDescriptor->getKeyDescriptor();
    }

    /**
     * @param array $keyDescriptors
     * @param string $signingOrEncrypt
     * @return KeyDescriptor[]
     */
    private function keyDescriptorsByType(array $keyDescriptors, string $signingOrEncrypt)
    {

        /** @var KeyDescriptor[] $keyDescriptorsFiltered */
        $keyDescriptorsFiltered = array_filter(
            $keyDescriptors,
            function (KeyDescriptor $keyDescriptor) use ($signingOrEncrypt) {
                return $keyDescriptor->getUse() === $signingOrEncrypt;
            }
        );

        return $keyDescriptorsFiltered;
    }

    /**
     * @param KeyDescriptor $keyDescriptor
     * @return array|X509Certificate[]
     */
    private function x509Certificates(KeyDescriptor $keyDescriptor)
    {

        $x509Datas = array_filter(
            $keyDescriptor->getKeyInfo()->getInfo(),
            function ($keyInfo) {
                return $keyInfo instanceof X509Data;
            }
        );

        /** @var X509Certificate[] $x509Certificates */
        $x509Certificates = [];

        /** @var X509Data $x509Data */
        foreach ($x509Datas as $x509Data) {
            $x509Certificates = array_filter(
                $x509Data->getData(),
                function ($datum) {
                    return $datum instanceof X509Certificate;
                }
            );
        }

        return $x509Certificates;
    }

    /**
     * @return string
     */
    public function toXmlString()
    {
        return $this->getMetadataModel()->toXML()->ownerDocument->saveXML();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toXmlString();
    }
}
