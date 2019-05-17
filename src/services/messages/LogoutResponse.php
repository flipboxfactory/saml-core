<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use craft\base\Component;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use SAML2\Constants;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse as SamlLogoutResponse;
use yii\base\Event;

/**
 * Class AbstractLogoutResponse
 * @package flipbox\saml\core\services\messages
 */
class LogoutResponse extends Component
{
    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @inheritdoc
     */
    public function create(
        LogoutRequest $request,
        AbstractProvider $theirProvider,
        AbstractProvider $ourProvider
    ) {
        $logout = new SamlLogoutResponse();

        /**
         * Set remote destination
         */
        $logout->setDestination(

            $theirProvider->getType() === SettingsInterface::SP ?
                $theirProvider->firstSpSloService(
                    /**
                    * We only support post right now
                    */
                    Constants::BINDING_HTTP_POST
                )->getResponseLocation() :
                $theirProvider->firstIdpSloService(

                    /**
                    * We only support post right now
                    */
                    Constants::BINDING_HTTP_POST
                )->getResponseLocation()
        );

        /**
         * Set session id
         */
        $logout->setInResponseTo(
            $request->getSessionIndex()
        );

        $logout->setRelayState(
            $request->getRelayState()
        );

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
         * request
         */
        $event->sender = $request;
        /**
         * response
         */
        $event->data = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }
}
