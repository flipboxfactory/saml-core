<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use flipbox\saml\core\events\LogoutResponseCreated;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\services\PluginService;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse as SamlLogoutResponse;
use SAML2\XML\saml\Issuer;

/**
 * Class AbstractLogoutResponse
 */
class LogoutResponse extends PluginService
{
    /**
     * {@inheritdoc}
     */
    public function create(
        LogoutRequest $request,
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider,
    ): SamlLogoutResponse {
        $logout = new SamlLogoutResponse;

        /**
         * Set remote destination
         */
        $logout->setDestination(
            $theirProvider->getType() === SettingsInterface::SP ?
                $theirProvider->firstSpSloService(
                )->getLocation() :
                $theirProvider->firstIdpSloService(
                )->getLocation()
        );

        /**
         * Set session id
         */
        // InResponseTo is the ID of the request being answered (SAML core 3.2.2)
        $logout->setInResponseTo(
            $request->getId()
        );

        $logout->setRelayState(
            $request->getRelayState()
        );

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
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        event(new LogoutResponseCreated($this->getPlugin(), $logout));

        return $logout;
    }
}
