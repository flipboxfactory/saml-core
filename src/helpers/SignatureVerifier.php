<?php

declare(strict_types=1);

namespace flipbox\saml\core\helpers;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\exceptions\InvalidSignature;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\SignedElement;
use Throwable;

/**
 * Verifies a SAML message or element against a provider's signing keys. Fails closed.
 *
 * Works for both bindings: saml2 attaches the HTTP-Redirect query signature to the received message, and the XML
 * signature for HTTP-POST.
 */
class SignatureVerifier
{
    /**
     * @throws InvalidSignature if the provider has no signing keys, the element isn't signed, or no key verifies it
     */
    public static function verify(SignedElement $element, AbstractProvider $provider): void
    {
        static::verifyWithKeys($element, $provider->signingXMLSecurityKeyStore());
    }

    /**
     * @param  list<XMLSecurityKey>  $keys
     *
     * @throws InvalidSignature
     */
    public static function verifyWithKeys(SignedElement $element, array $keys): void
    {
        if ($keys === []) {
            throw new InvalidSignature('The provider’s metadata has no signing certificate to verify the message with.');
        }

        foreach ($keys as $key) {
            try {
                // false: not signed; throws: signed, but not by this key (or invalid)
                if ($element->validate($key) === true) {
                    return;
                }

                throw new InvalidSignature('The message isn’t signed.');
            } catch (InvalidSignature $e) {
                throw $e;
            } catch (Throwable $e) {
                // Another of the provider's keys may have signed it
                Log::info('Signature didn’t verify with one of the provider’s keys: '.$e->getMessage(), ['category' => AbstractPlugin::SAML_CORE_HANDLE]);
            }
        }

        throw new InvalidSignature('The message’s signature didn’t verify with the provider’s signing certificates.');
    }
}
