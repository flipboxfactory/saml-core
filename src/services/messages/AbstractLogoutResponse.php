<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\ProviderInterface;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutRequest as LogoutRequestModel;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\StatusResponse;
use LightSaml\SamlConstants;
use yii\base\Event;
use flipbox\saml\core\models\SettingsInterface;

abstract class AbstractLogoutResponse extends AbstractLogout implements EnsureSAMLPlugin
{
    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @inheritdoc
     */
    public function create(AbstractRequest $samlMessage, array $config = []): StatusResponse
    {
        /** @var LogoutRequestModel $request */
        $request = $samlMessage;

        /**
         * NOTE: $provider is the remote provider
         */
        $provider = $this->getPlugin()->getHttpPost()->getProviderByIssuer(
            $request->getIssuer()
        );

        /** @var ProviderInterface $ownProvider */
        $ownProvider = $this->getPlugin()->getProvider()->findOwn();

        $logout = new LogoutResponse();

        /**
         * Set remote destination
         */
        $logout->setDestination(

            $provider->getType() === SettingsInterface::SP ?
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                    /**
                    * We only support post right now
                    */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )->getLocation() :
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                    /**
                    * We only support post right now
                    */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )->getLocation()
        );

        /**
         * Set session id
         */
        $logout->setInResponseTo(
            $request->getSessionIndex()
        );

        /**
         * Set issuer
         */
        $logout->setIssuer(
            $issuer = new Issuer(
                $ownProvider->getEntityId()
            )
        );

        /**
         * Sign the message
         */
        if ($ownProvider->keychain) {
            SecurityHelper::signMessage($logout, $ownProvider->keychain);
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        $event = new Event();
        /**
         * request
         */
        $event->sender = $samlMessage;
        /**
         * response
         */
        $event->data = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }
}
