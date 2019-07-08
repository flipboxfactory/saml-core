<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use craft\base\Component;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use yii\base\Event;
use SAML2\LogoutRequest as SamlLogoutRequest;

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
        string $relayState = ''
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
        $event = new Event();

        /**
         * response
         */
        $event->data = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }
}
