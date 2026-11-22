<?php

declare(strict_types=1);

use flipbox\saml\core\helpers\EntityDescriptorHelper;
use SAML2\Constants;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\IndexedEndpointType;
use SAML2\XML\md\SPSSODescriptor;

function endpoint(string $binding, string $location, ?int $index = null): EndpointType
{
    $endpoint = $index === null ? new EndpointType : new IndexedEndpointType;
    $endpoint->setBinding($binding);
    $endpoint->setLocation($location);
    if ($endpoint instanceof IndexedEndpointType) {
        $endpoint->setIndex((int) $index);
    }

    return $endpoint;
}

beforeEach(function () {
    $sp = new SPSSODescriptor;
    $sp->addAssertionConsumerService(endpoint(Constants::BINDING_HTTP_POST, 'https://sp.example.com/acs/post', 0));
    $sp->addAssertionConsumerService(endpoint(Constants::BINDING_HTTP_REDIRECT, 'https://sp.example.com/acs/redirect', 1));
    $sp->addSingleLogoutService(endpoint(Constants::BINDING_HTTP_REDIRECT, 'https://sp.example.com/slo'));

    $idp = new IDPSSODescriptor;
    $idp->addSingleSignOnService(endpoint(Constants::BINDING_HTTP_REDIRECT, 'https://idp.example.com/sso'));

    $this->entityDescriptor = new EntityDescriptor;
    $this->entityDescriptor->setEntityID('https://example.com');
    $this->entityDescriptor->addRoleDescriptor($sp);
    $this->entityDescriptor->addRoleDescriptor($idp);
});

it('filters descriptors by role', function () {
    $sp = EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor);
    $idp = EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor);

    expect($sp)->toHaveCount(1)->and($sp[0])->toBeInstanceOf(SPSSODescriptor::class)
        ->and($idp)->toHaveCount(1)->and($idp[0])->toBeInstanceOf(IDPSSODescriptor::class);
});

it('returns the first endpoint without a binding', function () {
    $acs = EntityDescriptorHelper::getFirstSpAssertionConsumerService(EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor));

    expect($acs->getLocation())->toBe('https://sp.example.com/acs/post');
});

it('matches the requested binding', function () {
    $sp = EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor);

    expect(EntityDescriptorHelper::getFirstSpAssertionConsumerService($sp, Constants::BINDING_HTTP_REDIRECT)->getLocation())->toBe('https://sp.example.com/acs/redirect')
        ->and(EntityDescriptorHelper::getFirstSpAssertionConsumerService($sp, Constants::BINDING_HTTP_ARTIFACT))->toBeNull();
});

it('finds SLO and SSO services, or null', function () {
    expect(EntityDescriptorHelper::getFirstSLOService(EntityDescriptorHelper::getSpDescriptors($this->entityDescriptor))->getLocation())->toBe('https://sp.example.com/slo')
        ->and(EntityDescriptorHelper::getFirstIdpSSOService(EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor))->getLocation())->toBe('https://idp.example.com/sso')
        ->and(EntityDescriptorHelper::getFirstSLOService(EntityDescriptorHelper::getIdpDescriptors($this->entityDescriptor)))->toBeNull();
});
