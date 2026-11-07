<?php

namespace flipbox\saml\core\tests\fixtures;

/**
 * Generates a throwaway RSA key pair and self-signed certificate so no key material is committed.
 */
final class KeyPair
{
    public function __construct(
        public readonly string $privateKeyPem,
        public readonly string $certificatePem,
    ) {
    }

    public static function generate(string $digest = 'sha256'): self
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $csr = openssl_csr_new(['commonName' => 'saml-core-test'], $key, ['digest_alg' => $digest]);
        $certificate = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => $digest]);

        openssl_pkey_export($key, $privateKeyPem);
        openssl_x509_export($certificate, $certificatePem);

        return new self($privateKeyPem, $certificatePem);
    }
}
