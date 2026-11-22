<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use flipbox\saml\core\events\LogoutRequestCreated;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
use flipbox\saml\core\services\PluginService;
use SAML2\Constants;
use SAML2\HTTPRedirect;
use SAML2\LogoutRequest as SamlLogoutRequest;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;

/**
 * Class LogoutRequest
 */
class LogoutRequest extends PluginService
{
    /**
     * @throws \Exception
     */
    public function create(
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider,
        AbstractProviderIdentity $identity,
        ?string $relayState = null,
    ): SamlLogoutRequest {
        $logout = new SamlLogoutRequest;

        /**
         * Set remote destination
         */
        $logout->setDestination(
            $theirProvider->getType() === SettingsInterface::SP ?
                $theirProvider->firstSpSloService()->getLocation() :
                $theirProvider->firstIdpSloService()->getLocation()
        );

        /**
         * Set session id
         */
        $logout->setSessionIndex($identity->sessionId);

        $logout->setNotOnOrAfter(
            (new \DateTime('+5 minutes'))->getTimestamp()
        );
        $logout->setIssueInstant(
            (new \DateTime)->getTimestamp()
        );
        $logout->setConsent(
            Constants::CONSENT_UNSPECIFIED
        );

        $logout->setRelayState(
            $relayState
        );

        /**
         * Set NameId
         */
        $logout->setNameID(
            $nameId = new NameID
        );

        $nameId->setValue($identity->nameId);
        $nameId->setFormat(Constants::NAMEID_EMAIL_ADDRESS);

        /**
         * Set issuer
         */
        $logout->setIssuer(
            $issuer = new Issuer
        );
        $issuer->setValue(
            $ourProvider->getEntityId()
        );

        /**
         * Sign the message
         */
        if ($ourProvider->keychain) {
            $logout->setSignatureKey(
                $ourProvider->keychainPrivateXmlSecurityKey()
            );

            // The decrypted PEM certificate, without armour: the stored column is ciphertext when encrypted at rest
            $logout->setCertificates([
                SecurityHelper::cleanCertificate($ourProvider->keychain->getDecryptedCertificate()),
            ]);
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        event(new LogoutRequestCreated($this->getPlugin(), $logout));

        return $logout;
    }

    public function createRedirectUrl(
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider,
        AbstractProviderIdentity $identity,
        ?string $relayState = null,
    ): string {
        return (new HTTPRedirect)->getRedirectURL(
            $this->create(
                $theirProvider,
                $ourProvider,
                $identity,
                $relayState
            )
        );
    }
}
