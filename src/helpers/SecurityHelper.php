<?php

namespace flipbox\saml\core\helpers;

use flipbox\saml\core\AbstractPlugin;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\EncryptedAssertion;
use SAML2\Utilities\Certificate;

class SecurityHelper
{
    public const XMLDSIG_DIGEST_MD5 = 'http://www.w3.org/2001/04/xmldsig-more#md5';

    /**
     * @var array<string, string>
     */
    private static array $typeMap = [
        'RSA-SHA1' => XMLSecurityKey::RSA_SHA1,
        'RSA-SHA256' => XMLSecurityKey::RSA_SHA256,
        'RSA-SHA384' => XMLSecurityKey::RSA_SHA384,
        'RSA-SHA512' => XMLSecurityKey::RSA_SHA512,
    ];

    /**
     * @var list<string>
     */
    public static array $validEncryptionMethods = [
        XMLSecurityKey::TRIPLEDES_CBC,
        // Prefered
        XMLSecurityKey::AES128_CBC,
        XMLSecurityKey::AES192_CBC,
        XMLSecurityKey::AES256_CBC,

        XMLSecurityKey::RSA_1_5,
        XMLSecurityKey::RSA_SHA1,
        XMLSecurityKey::RSA_OAEP_MGF1P,
    ];

    /**
     * @return string
     */
    public static function convertToCertificate(string $certificate)
    {
        return Certificate::convertToCertificate(
            static::cleanCertificate($certificate)
        );
    }

    /**
     * @return string|string[]|null
     */
    public static function cleanCertificate(string $certificate)
    {
        if (preg_match(Certificate::CERTIFICATE_PATTERN, $certificate, $matches) == false) {
            throw new \InvalidArgumentException('Invalid PEM encoded certificate');
        }

        return static::cleanCertificateWhiteSpace($matches[1]);
    }

    /**
     * @return string|string[]|null
     */
    public static function cleanCertificateWhiteSpace(string $certificate)
    {
        return preg_replace('/\s+/', '', $certificate);
    }

    /**
     * The XML security algorithm matching a certificate's signature algorithm (from lightsaml/lightsaml).
     *
     * @throws \Exception if it's not recognised
     */
    public static function getPemAlgorithm(string $pem): string
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
            throw new \Exception('Unrecognized signature algorithm');
        }

        return $signatureAlgorithm;
    }

    /**
     * @throws \Exception
     */
    /**
     * @param  list<string>  $blacklist  algorithms that must not be used
     */
    public static function decryptAssertion(EncryptedAssertion $encryptedAssertion, string $pemString, array $blacklist = []): Assertion
    {
        $lastException = null;
        foreach (static::$validEncryptionMethods as $method) {
            Log::debug('Trying method: '.$method, ['category' => AbstractPlugin::SAML_CORE_HANDLE]);
            if (in_array($method, $blacklist)) {
                Log::debug('Decryption with key #'.$method.' blacklisted.', ['category' => AbstractPlugin::SAML_CORE_HANDLE]);

                continue;
            }
            $xmlSecurityKey = new XMLSecurityKey($method, [
                'type' => 'private',
            ]);

            $xmlSecurityKey->loadKey(
                $pemString,
                false,
                false
            );

            try {
                $assertion = $encryptedAssertion->getAssertion(
                    $xmlSecurityKey,
                    $blacklist
                );
                Log::debug('Decryption with key #'.$method.' succeeded.', ['category' => AbstractPlugin::SAML_CORE_HANDLE]);

                return $assertion;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::debug('Decryption with key #'.$method.' failed. ', ['category' => AbstractPlugin::SAML_CORE_HANDLE]);
            }
        }

        // Finally, throw it
        throw $lastException;
    }
}
