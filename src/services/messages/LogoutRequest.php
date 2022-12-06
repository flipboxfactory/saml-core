<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use craft\base\Component;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
use flipbox\saml\core\events\UserLogout;
use SAML2\Constants;
use SAML2\HTTPRedirect;
use SAML2\LogoutRequest as SamlLogoutRequest;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use yii\base\Event;

/**
 * Class LogoutRequest
 * @package flipbox\saml\core\services\messages
 */
class LogoutRequest extends Component
{


    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @param AbstractProvider $theirProvider
     * @param AbstractProvider $ourProvider
     * @param AbstractProviderIdentity $identity
     * @return SamlLogoutRequest
     * @throws \Exception
     */
    public function create(
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider,
        AbstractProviderIdentity $identity,
        string $relayState = null
    ) {

        $logout = new SamlLogoutRequest();

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
            (new \DateTime())->getTimestamp()
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
            $nameId = new NameID()
        );

        $nameId->setValue($identity->nameId);
        $nameId->setFormat(Constants::NAMEID_EMAIL_ADDRESS);

        /**
         * Set issuer
         */
        $logout->setIssuer(
            $issuer = new Issuer()
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

            $logout->setCertificates([
                $ourProvider->keychain->certificate
            ]);
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        $event = new UserLogout();

        /**
         * response
         */
        $event->request = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }

    public function createRedirectUrl(
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider,
        AbstractProviderIdentity $identity,
        string $relayState = null
    ) {
        return (new HTTPRedirect())->getRedirectURL(
            $this->create(
                $theirProvider,
                $ourProvider,
                $identity,
                $relayState
            )
        );
    }
}
