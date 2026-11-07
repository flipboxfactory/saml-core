<?php

namespace flipbox\saml\core\tests\unit\helpers;

use Codeception\Test\Unit;
use flipbox\saml\core\helpers\EntityDescriptorHelper;
use SAML2\Constants;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\SPSSODescriptor;

class EntityDescriptorHelperTest extends Unit
{
    private EntityDescriptor $entityDescriptor;

    protected function _before(): void
    {
        $sp = new SPSSODescriptor();
        $sp->addAssertionConsumerService($this->indexedEndpoint(Constants::BINDING_HTTP_POST, 'https://sp.example.com/acs/post', 0));
        $sp->addAssertionConsumerService($this->indexedEndpoint(Constants::BINDING_HTTP_REDIRECT, 'https://sp.example.com/acs/redirect', 1));
        $sp->addSingleLogoutService($this->endpoint(Constants::BINDING_HTTP_REDIRECT, 'https://sp.example.com/slo'));

        $idp = new IDPSSODescriptor();
        $idp->addSingleSignOnService($this->endpoint(Constants::BINDING_HTTP_REDIRECT, 'https://idp.example.com/sso'));

        $this->entityDescriptor = new EntityDescriptor();
        $this->entityDescriptor->setEntityID('https://example.com');
        $this->entityDescriptor->addRoleDescriptor($sp);
        $this->entityDescriptor->addRoleDescriptor($idp);
    }

    public function testDescriptorsAreFilteredByRole(): void
    {
        $sp = EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor);
        $idp = EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor);

        $this->assertCount(1, $sp);
        $this->assertInstanceOf(SPSSODescriptor::class, $sp[0]);
        $this->assertCount(1, $idp);
        $this->assertInstanceOf(IDPSSODescriptor::class, $idp[0]);
    }

    public function testFirstServiceWithoutBindingIsTheFirstEndpoint(): void
    {
        $acs = EntityDescriptorHelper::getFirstSpAssertionConsumerService(
            EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor)
        );

        $this->assertSame('https://sp.example.com/acs/post', $acs->getLocation());
    }

    public function testFirstServiceMatchesRequestedBinding(): void
    {
        $sp = EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor);

        $this->assertSame(
            'https://sp.example.com/acs/redirect',
            EntityDescriptorHelper::getFirstSpAssertionConsumerService($sp, Constants::BINDING_HTTP_REDIRECT)->getLocation()
        );
        $this->assertNull(EntityDescriptorHelper::getFirstSpAssertionConsumerService($sp, Constants::BINDING_HTTP_ARTIFACT));
    }

    public function testSloAndSsoServices(): void
    {
        $this->assertSame(
            'https://sp.example.com/slo',
            EntityDescriptorHelper::getFirstSLOService(EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor))->getLocation()
        );
        $this->assertSame(
            'https://idp.example.com/sso',
            EntityDescriptorHelper::getFirstIdpSSOService(EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor))->getLocation()
        );
    }

    public function testMissingServiceReturnsNull(): void
    {
        $this->assertNull(
            EntityDescriptorHelper::getFirstSLOService(EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor))
        );
    }

    private function endpoint(string $binding, string $location): EndpointType
    {
        $endpoint = new EndpointType();
        $endpoint->setBinding($binding);
        $endpoint->setLocation($location);

        return $endpoint;
    }

    private function indexedEndpoint(string $binding, string $location, int $index): IndexedEndpointType
    {
        $endpoint = new IndexedEndpointType();
        $endpoint->setBinding($binding);
        $endpoint->setLocation($location);
        $endpoint->setIndex($index);

        return $endpoint;
    }
}
