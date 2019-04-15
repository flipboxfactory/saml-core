<?php

namespace flipbox\saml\core\services\bindings;

use craft\base\Component;
use craft\web\Request;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\SamlConstants;

/**
 * Class AbstractFactory
 * @package flipbox\saml\core\services\bindings
 */
abstract class AbstractFactory extends Component
{
    use EnsureSamlPlugin;

    /**
     * @param string $binding
     * @return BindingInterface
     */
    public function getService(string $binding): BindingInterface
    {
        $service = $this->getSamlPlugin()->getHttpPost();
        if ($binding === SamlConstants::BINDING_SAML2_HTTP_REDIRECT) {
            $service = $this->getSamlPlugin()->getHttpRedirect();
        }

        return $service;
    }

    /**
     * @param Request $request
     * @return \LightSaml\Model\Protocol\AuthnRequest|\LightSaml\Model\Protocol\LogoutRequest|\LightSaml\Model\Protocol\LogoutResponse|\LightSaml\Model\Protocol\Response|SamlMessage
     * @throws \Exception
     * @throws \flipbox\saml\core\exceptions\InvalidSignature
     */
    public function receive(Request $request)
    {

        switch ($request->getMethod()) {
            case 'POST':
                $message = $this->getSamlPlugin()->getHttpPost()->receive($request);
                break;
            case 'GET':
            default:
                $message = $this->getSamlPlugin()->getHttpRedirect()->receive($request);
                break;
        }

        return $message;
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return mixed
     * @throws InvalidMetadata
     */
    public function send(SamlMessage $message, ProviderInterface $provider)
    {
        if ($provider->getType() === $provider::TYPE_IDP) {
            $binding = $this->determineBindingToIdp($message, $provider);
        } else {
            $binding = $this->determineBindingToSp($message, $provider);
        }

        return $this->getService($binding)->send($message, $provider);
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return string
     * @throws InvalidMetadata
     */
    public function determineBindingToSp(SamlMessage $message, ProviderInterface $provider)
    {
        $binding = SamlConstants::BINDING_SAML2_HTTP_POST;

        switch (true) {
            case MessageHelper::isRequest($message):
                if (! $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService(
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )) {
                    if ($provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService(
                        SamlConstants::BINDING_SAML2_HTTP_REDIRECT
                    )) {
                        $binding = SamlConstants::BINDING_SAML2_HTTP_REDIRECT;
                    } else {
                        throw new InvalidMetadata(
                            'Metabeta seems to be invalid. Provider ACS binding is not detected.'
                        );
                    }
                }

                break;
            case MessageHelper::isResponse($message):
                if (! $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )) {
                    if ($provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                        SamlConstants::BINDING_SAML2_HTTP_REDIRECT
                    )) {
                        $binding = SamlConstants::BINDING_SAML2_HTTP_REDIRECT;
                    } else {
                        throw new InvalidMetadata(
                            'Metabeta seems to be invalid. Provider SLO binding is not detected.'
                        );
                    }
                }
                break;
        }

        return $binding;
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return string
     * @throws InvalidMetadata
     */
    public function determineBindingToIdp(SamlMessage $message, ProviderInterface $provider)
    {

        $binding = SamlConstants::BINDING_SAML2_HTTP_POST;

        switch (true) {
            case MessageHelper::isRequest($message):
                if (! $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleSignOnService(
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )) {
                    if ($provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleSignOnService(
                        SamlConstants::BINDING_SAML2_HTTP_REDIRECT
                    )) {
                        $binding = SamlConstants::BINDING_SAML2_HTTP_REDIRECT;
                    } else {
                        throw new InvalidMetadata(
                            'Metabeta seems to be invalid. Provider SSO binding is not detected.'
                        );
                    }
                }
                break;
            case MessageHelper::isResponse($message):
                if (! $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )) {
                    if ($provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                        SamlConstants::BINDING_SAML2_HTTP_REDIRECT
                    )) {
                        $binding = SamlConstants::BINDING_SAML2_HTTP_REDIRECT;
                    } else {
                        throw new InvalidMetadata(
                            'Metabeta seems to be invalid. Provider SLO binding is not detected.'
                        );
                    }
                }

                break;
        }

        return $binding;
    }
}
