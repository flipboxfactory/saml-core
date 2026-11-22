<?php

declare(strict_types=1);

use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\tests\fixtures\KeyPair;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\AuthnRequest;
use SAML2\EncryptedAssertion;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\Response;
use SAML2\XML\saml\Issuer;

function issuer(string $value): Issuer
{
    $issuer = new Issuer;
    $issuer->setValue($value);

    return $issuer;
}

function encryptAssertion(Assertion $assertion, KeyPair $keyPair): EncryptedAssertion
{
    $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
    $key->loadKey($keyPair->certificatePem, false, true);

    $encrypted = new EncryptedAssertion;
    $encrypted->setAssertion($assertion, $key);

    // Round-trip through XML, as a real IdP response would arrive
    return new EncryptedAssertion($encrypted->toXML());
}

describe('MessageHelper', function () {
    it('generates unique, xs:ID-safe IDs', function () {
        $id = MessageHelper::generateId();

        expect(strlen($id))->toBe(MessageHelper::ID_LENGTH)
            ->and($id)->toMatch('/^_[0-9a-f]+$/')
            ->and(MessageHelper::generateId())->not->toBe($id);
    });

    it('classifies messages', function (string $class, bool $isRequest, string $parameter) {
        $message = new $class;

        expect(MessageHelper::isRequest($message))->toBe($isRequest)
            ->and(MessageHelper::isResponse($message))->toBe(! $isRequest)
            ->and(MessageHelper::getParameterKeyByMessage($message))->toBe($parameter);
    })->with([
        'AuthnRequest' => [AuthnRequest::class, true, MessageHelper::REQUEST_PARAMETER],
        'LogoutRequest' => [LogoutRequest::class, true, MessageHelper::REQUEST_PARAMETER],
        'Response' => [Response::class, false, MessageHelper::RESPONSE_PARAMETER],
        'LogoutResponse' => [LogoutResponse::class, false, MessageHelper::RESPONSE_PARAMETER],
    ]);

    it('reads issuers', function () {
        expect(MessageHelper::getIssuer(issuer('https://idp.example.com')))->toBe('https://idp.example.com')
            ->and(MessageHelper::getIssuer('https://idp.example.com'))->toBe('https://idp.example.com')
            ->and(MessageHelper::getIssuer(null))->toBeNull();
    });
});

describe('SecurityHelper', function () {
    beforeEach(fn () => $this->keyPair = KeyPair::generate());

    it('cleans certificates', function () {
        $clean = SecurityHelper::cleanCertificate($this->keyPair->certificatePem);

        expect($clean)->not->toContain('-----')->not->toMatch('/\s/')
            ->and(base64_decode($clean, true))->not->toBeFalse();
    });

    it('rejects input that isn’t a PEM certificate', function () {
        SecurityHelper::cleanCertificate('not a certificate');
    })->throws(InvalidArgumentException::class);

    it('converts to an equivalent PEM', function () {
        expect(openssl_x509_fingerprint(SecurityHelper::convertToCertificate($this->keyPair->certificatePem), 'sha256'))
            ->toBe(openssl_x509_fingerprint($this->keyPair->certificatePem, 'sha256'));
    });

    it('detects a certificate’s signature algorithm', function (string $digest, string $expected) {
        expect(SecurityHelper::getPemAlgorithm(KeyPair::generate($digest)->certificatePem))->toBe($expected);
    })->with([
        'sha256' => ['sha256', XMLSecurityKey::RSA_SHA256],
        'sha384' => ['sha384', XMLSecurityKey::RSA_SHA384],
        'sha512' => ['sha512', XMLSecurityKey::RSA_SHA512],
    ]);

    it('decrypts an encrypted assertion', function () {
        $assertion = new Assertion;
        $assertion->setIssuer(issuer('https://idp.example.com'));

        $decrypted = SecurityHelper::decryptAssertion(encryptAssertion($assertion, $this->keyPair), $this->keyPair->privateKeyPem);

        expect($decrypted->getId())->toBe($assertion->getId())
            ->and($decrypted->getIssuer()->getValue())->toBe('https://idp.example.com');
    });

    it('honours the algorithm blacklist', function () {
        $assertion = new Assertion;
        $assertion->setIssuer(issuer('https://idp.example.com'));

        SecurityHelper::decryptAssertion(encryptAssertion($assertion, $this->keyPair), $this->keyPair->privateKeyPem, [XMLSecurityKey::RSA_OAEP_MGF1P]);
    })->throws(Exception::class);

    it('fails with the wrong key', function () {
        $assertion = new Assertion;
        $assertion->setIssuer(issuer('https://idp.example.com'));

        SecurityHelper::decryptAssertion(encryptAssertion($assertion, $this->keyPair), KeyPair::generate()->privateKeyPem);
    })->throws(Exception::class);
});
