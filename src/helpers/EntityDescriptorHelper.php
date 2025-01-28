<?php


namespace flipbox\saml\core\helpers;

use SAML2\Constants;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\SPSSODescriptor;
use SAML2\XML\md\SSODescriptorType;
use SAML2\XML\md\RoleDescriptor;

class EntityDescriptorHelper
{
    const ENDPOINT_SERVICE_ARTIFACT_RESOLUTION = 'ArtifactResolution';
    const ENDPOINT_SERVICE_SINGLE_LOGOUT = 'SingleLogout';
    const ENDPOINT_SERVICE_MANAGE_NAME_ID = 'ManageNameID';

    const ENDPOINT_SERVICE_ASSERTION_CONSUMER = 'AssertionConsumer';
    const ENDPOINT_SERVICE_ATTRIBUTE_CONSUMING = 'AttributeConsuming';

    const ENDPOINT_SERVICE_SINGLE_SIGN_ON = 'SingleSignOn';
    const ENDPOINT_SERVICE_ASSERTION_ID_REQUEST = 'AssertionIDRequest';
    const ENDPOINT_SERVICE_NAME_ID_MAPPING = 'NameIDMapping';

    const ENDPOINT_SERVICE_OPTIONS = [
        // Common
        self::ENDPOINT_SERVICE_ARTIFACT_RESOLUTION,
        self::ENDPOINT_SERVICE_SINGLE_LOGOUT,
        self::ENDPOINT_SERVICE_MANAGE_NAME_ID,

        // SP
        self::ENDPOINT_SERVICE_ASSERTION_CONSUMER,
        self::ENDPOINT_SERVICE_ATTRIBUTE_CONSUMING,

        // IDP
        self::ENDPOINT_SERVICE_SINGLE_SIGN_ON,
        self::ENDPOINT_SERVICE_ASSERTION_ID_REQUEST,
        self::ENDPOINT_SERVICE_NAME_ID_MAPPING,
    ];

    /**
     * @param  EntityDescriptor $entityDescriptor
     * @return IDPSSODescriptor[]
     */
    public static function getIdpDescriptors(EntityDescriptor $entityDescriptor): array
    {
        return static::getDescriptors($entityDescriptor, IDPSSODescriptor::class);
    }

    /**
     * @param  EntityDescriptor $entityDescriptor
     * @return SPSSODescriptor[]
     */
    public static function getSpDescriptors(EntityDescriptor $entityDescriptor): array
    {
        return static::getDescriptors($entityDescriptor, SPSSODescriptor::class);
    }

    /**
     * @param  EntityDescriptor $entityDescriptor
     * @param  string           $type
     * @return SSODescriptorType[]
     */
    protected static function getDescriptors(EntityDescriptor $entityDescriptor, string $type): array
    {
        $descriptors = [];
        foreach ($entityDescriptor->getRoleDescriptor() as $roleDescriptor) {
            if ($roleDescriptor instanceof $type) {
                $descriptors[] = $roleDescriptor;
            }
        }

        return $descriptors;
    }

    /**
     * Common
     */

    /**
     * @param  SSODescriptorType[] $roleDescriptors
     * @param  string              $binding
     * @return EndpointType|null
     */
    public static function getFirstArtifactResolutionService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_ARTIFACT_RESOLUTION, $roleDescriptors, $binding);
    }

    /**
     * @param  SSODescriptorType[] $roleDescriptors
     * @param  string              $binding
     * @return EndpointType|null
     */
    public static function getFirstSLOService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_SINGLE_LOGOUT, $roleDescriptors, $binding);
    }

    /**
     * @param  SSODescriptorType[] $roleDescriptors
     * @param  string              $binding
     * @return EndpointType|null
     */
    public static function getFirstManageNameIDService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_MANAGE_NAME_ID, $roleDescriptors, $binding);
    }

    /**
     * Get First SP Services
     */

    /**
     * @param  SPSSODescriptor[] $roleDescriptors
     * @param  string            $binding
     * @return EndpointType|null
     */
    public static function getFirstSpAssertionConsumerService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_ASSERTION_CONSUMER, $roleDescriptors, $binding);
    }

    /**
     * @param  SPSSODescriptor[] $roleDescriptors
     * @param  string            $binding
     * @return EndpointType|null
     */
    public static function getFirstSpAttributeConsumingService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_ATTRIBUTE_CONSUMING, $roleDescriptors, $binding);
    }

    /**
     * Get First IDP Services
     */

    /**
     * @param  IDPSSODescriptor[] $roleDescriptors
     * @param  string             $binding
     * @return EndpointType|null
     */
    public static function getFirstIdpSSOService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_SINGLE_SIGN_ON, $roleDescriptors, $binding);
    }

    /**
     * @param  IDPSSODescriptor[] $roleDescriptors
     * @param  string             $binding
     * @return EndpointType|null
     */
    public static function getFirstIdpAssertionIdRequestService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_ASSERTION_ID_REQUEST, $roleDescriptors, $binding);
    }

    /**
     * @param  IDPSSODescriptor[] $roleDescriptors
     * @param  string             $binding
     * @return EndpointType|null
     */
    public static function getFirstIdpNameIDMappingService(array $roleDescriptors, string $binding = null): ?EndpointType
    {
        return static::getFirstService(self::ENDPOINT_SERVICE_NAME_ID_MAPPING, $roleDescriptors, $binding);
    }

    /**
     * @param  SSODescriptorType[] $roleDescriptors
     * @param  string              $binding
     * @param  string              $service
     * @return EndpointType|null
     */
    protected static function getFirstService(string $service, array $roleDescriptors, string $binding = null): ?EndpointType
    {

        if (! in_array($service, static::ENDPOINT_SERVICE_OPTIONS)) {
            throw new \InvalidArgumentException('Unknown service passed: ' . $service);
        }

        \Craft::info(
            sprintf(
                'Looping thru %s role descriptors',
                count($roleDescriptors)
            ),
            'saml-core'
        );

        $serviceMethod = 'get' . $service . 'Service';

        \Craft::info(
            'Using Service method: ' . $serviceMethod,
            'saml-core'
        );

        $return = null;
        foreach ($roleDescriptors as $descriptor) {
            /*if (static::isRoleDescriptorSaml($descriptor) && $return = static::getFirstIndexedEndpointType(*/
            if ($return = static::getFirstIndexedEndpointType(
                call_user_func([$descriptor, $serviceMethod]),
                $binding
            )
            ) {
                break;
            }
        }

        return $return;
    }

    /**
     * @param  RoleDescriptor $roleDescriptor
     * @return bool
     */
    protected static function isRoleDescriptorSaml(RoleDescriptor $roleDescriptor): bool
    {
        foreach ($roleDescriptor->getProtocolSupportEnumeration() as $protocolSupportEnumeration) {
            if ($protocolSupportEnumeration === Constants::NS_SAMLP) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param  IndexedEndpointType[] $endpointTypes
     * @param  $binding
     * @return EndpointType|null
     */
    protected static function getFirstIndexedEndpointType(array $endpointTypes, string $binding = null)
    {

        // Is there one?
        if (!isset($endpointTypes[0])) {
            return null;
        }

        // Default to the first one
        $return = $endpointTypes[0];
        if (is_null($binding)) {
            return $return;
        }

        // Reset the return
        $return = null;

        /**
 * @var EndpointType $endpointType 
*/
        foreach ($endpointTypes as $endpointType) {
            if (! $endpointType instanceof EndpointType) {
                throw new \InvalidArgumentException();
            }

            if ($endpointType->getBinding() === $binding) {
                $return = $endpointType;
                break;
            }
        }

        return $return;
    }
}
