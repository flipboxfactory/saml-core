<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;


use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutRequest as LogoutRequestModel;
use LightSaml\SamlConstants;

abstract class AbstractLogoutRequest extends AbstractLogout implements SamlRequestInterface
{
    use EnsureSamlPlugin;


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
        );

        /**
         * Set remote destination
         */
        $logout->setDestination(
            $provider->getType() === AbstractPlugin::SP ?
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                /**
                 * We only support post right now
                 */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                ) :
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                /**
                 * We only support post right now
                 */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )
        );

        /**
         * Set session id
         */
        $logout->setSessionIndex($providerIdentity->sessionId);

        /**
         * Set NameId
         */
        $logout->setNameID(
            $nameId = new NameID($providerIdentity->nameId)
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

        return $logout;
    }
}