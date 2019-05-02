<?php

namespace flipbox\saml\core\helpers;

use flipbox\keychain\records\KeyChainRecord;
use LightSaml\Credential\KeyHelper;
use LightSaml\Credential\KeyHelper as LightSamlKeyHelper;
use LightSaml\Credential\X509Certificate;
use LightSaml\Error\LightSamlSecurityException;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\Model\XmlDSig\SignatureXmlReader;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Utilities\Certificate;
use SAML2\Utils;

class SecurityHelper
{

    const XMLDSIG_DIGEST_MD5 = 'http://www.w3.org/2001/04/xmldsig-more#md5';

    private static $typeMap = [
        'RSA-SHA1' => XMLSecurityKey::RSA_SHA1,
        'RSA-SHA256' => XMLSecurityKey::RSA_SHA256,
        'RSA-SHA384' => XMLSecurityKey::RSA_SHA384,
        'RSA-SHA512' => XMLSecurityKey::RSA_SHA512,
    ];

    public static function castAssertionEncryptionKey(XMLSecurityKey $key)
    {
        return Utils::castKey($key, XMLSecurityKey::RSA_1_5);
    }

    /**
     * @param string $certificate
     * @return string
     */
    public static function convertToCertificate(string $certificate)
    {
        return Certificate::convertToCertificate(
            static::cleanCertificate($certificate)
        );
    }

    /**
     * @param string $certificate
     * @return string|string[]|null
     */
    public static function cleanCertificate(string $certificate)
    {
        if (false == preg_match(Certificate::CERTIFICATE_PATTERN, $certificate, $matches)) {
            throw new \InvalidArgumentException('Invalid PEM encoded certificate');
        }

        return static::cleanCertificateWhiteSpace($matches[1]);
    }

    public static function cleanCertificateWhiteSpace(string $certificate)
    {
        return preg_replace('/\s+/', '', $certificate);
    }

    public static function getPemAlgorithm($pem)
    {
        $res = openssl_x509_read($pem);
        $info = openssl_x509_parse($res);
        $signatureAlgorithm = null;
        $signatureType = isset($info['signatureTypeSN']) ? $info['signatureTypeSN'] : '';
        if ($signatureType && isset(self::$typeMap[$signatureType])) {
            $signatureAlgorithm = self::$typeMap[$signatureType];
        } else {
            openssl_x509_export($res, $out, false);
            if (preg_match('/^\s+Signature Algorithm:\s*(.*)\s*$/m', $out, $match)) {
                switch ($match[1]) {
                    case 'sha1WithRSAEncryption':
                    case 'sha1WithRSA':
                        $signatureAlgorithm = XMLSecurityKey::RSA_SHA1;
                        break;
                    case 'sha256WithRSAEncryption':
                    case 'sha256WithRSA':
                        $signatureAlgorithm = XMLSecurityKey::RSA_SHA256;
                        break;
                    case 'sha384WithRSAEncryption':
                    case 'sha384WithRSA':
                        $signatureAlgorithm = XMLSecurityKey::RSA_SHA384;
                        break;
                    case 'sha512WithRSAEncryption':
                    case 'sha512WithRSA':
                        $signatureAlgorithm = XMLSecurityKey::RSA_SHA512;
                        break;
                    case 'md5WithRSAEncryption':
                    case 'md5WithRSA':
                        $signatureAlgorithm = static::XMLDSIG_DIGEST_MD5;
                        break;
                    default:
                }
            }
        }

        if (! $signatureAlgorithm) {
            throw new LightSamlSecurityException('Unrecognized signature algorithm');
        }

        return $signatureAlgorithm;
    }

}
