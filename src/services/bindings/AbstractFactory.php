<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/24/18
 * Time: 8:11 PM
 */

namespace flipbox\saml\core\services\bindings;


use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\SamlPluginInterface;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\SamlConstants;
use craft\web\Request;

/**
 * Class AbstractFactory
 * @package flipbox\saml\core\services\bindings
 */
abstract class AbstractFactory
{

    /**
     * @return SamlPluginInterface
     */
    abstract protected static function getSamlPlugin(): SamlPluginInterface;

    /**
     * @param string $binding
     * @return BindingInterface
     */
    public static function getService(string $binding): BindingInterface
    {
        $service = static::getSamlPlugin()->getHttpPost();
        if ($binding === SamlConstants::BINDING_SAML2_HTTP_REDIRECT) {
            $service = static::getSamlPlugin()->getHttpRedirect();
        }

        return $service;
    }

    /**
     * @param Request $request
     * @return \LightSaml\Model\Protocol\AuthnRequest|\LightSaml\Model\Protocol\LogoutRequest|\LightSaml\Model\Protocol\LogoutResponse|\LightSaml\Model\Protocol\Response|SamlMessage
     * @throws \Exception
     * @throws \flipbox\saml\core\exceptions\InvalidSignature
     */
    public static function receive(Request $request)
    {

        switch ($request->getMethod()) {
            case 'POST':
                $message = static::getSamlPlugin()->getHttpPost()->receive($request);
                break;
            case 'GET':
            default:
                $message = static::getSamlPlugin()->getHttpRedirect()->receive($request);
                break;
        }

        return $message;
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return \flipbox\saml\core\models\Transport
     * @throws InvalidMetadata
     */
    public static function send(SamlMessage $message, ProviderInterface $provider)
    {
        $binding = SamlConstants::BINDING_SAML2_HTTP_POST;
        if ($provider->getType() === $provider::TYPE_IDP) {
            $binding = static::determineBindingToIdp($message, $provider);
        } else {
            $binding = static::determineBindingToSp($message, $provider);
        }

//        InResponseTo

        return static::getService($binding)->send($message,  $provider);
    }

    /**
     * @param SamlMessage $message
     * @param ProviderInterface $provider
     * @return string
     * @throws InvalidMetadata
     */
    public static function determineBindingToSp(SamlMessage $message, ProviderInterface $provider)
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
                        throw new InvalidMetadata('Metabeta seems to be invalid. Provider ACS binding is not detected.');
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
                        throw new InvalidMetadata('Metabeta seems to be invalid. Provider SLO binding is not detected.');
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
    public static function determineBindingToIdp(SamlMessage $message, ProviderInterface $provider)
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
                        throw new InvalidMetadata('Metabeta seems to be invalid. Provider SSO binding is not detected.');
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
                        throw new InvalidMetadata('Metabeta seems to be invalid. Provider SLO binding is not detected.');
                    }
                }

                break;
        }

        return $binding;
    }

}