<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;

use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\ProviderInterface;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutRequest as LogoutRequestModel;
use LightSaml\SamlConstants;
use yii\base\Event;

abstract class AbstractLogoutRequest extends AbstractLogout implements EnsureSAMLPlugin
{


    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @inheritdoc
     */
    public function create(ProviderInterface $provider, array $config = []): AbstractRequest
    {
        /**
         * NOTE: $provider is the remote provider
         */

        $ownProvider = $this->getSamlPlugin()->getProvider()->findOwn();

        $logout = new LogoutRequestModel();

        /**
         * Get stored identity
         */
        $providerIdentity = $this->getSamlPlugin()->getProviderIdentity()->findByUser(
            \Craft::$app->getUser()->getIdentity()
        )->one();

        /**
         * Set remote destination
         */
        $logout->setDestination(
            $provider->getType() === AbstractPlugin::SP ?
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                )->getLocation() :
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                )->getLocation()
        );
        /**
         * Generate the request id
         */
        $logout->setID(
            Helper::generateID()
        );


        /**
         * Set session id
         */
        $logout->setSessionIndex($providerIdentity->sessionId);

        $logout->setNotOnOrAfter(
            new \DateTime('+5 minutes')
        )->setReason(
            SamlConstants::LOGOUT_REASON_USER
        )->setIssueInstant(
            new \DateTime()
        )->setConsent(
            SamlConstants::CONSENT_UNSPECIFIED
        );


        /**
         * Set NameId
         */
        $logout->setNameID(
            $nameId = new NameID($providerIdentity->nameId, SamlConstants::NAME_ID_FORMAT_EMAIL)
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
         * response
         */
        $event->data = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }
}
