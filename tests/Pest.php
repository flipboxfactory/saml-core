<?php

declare(strict_types=1);

use flipbox\saml\core\tests\TestCase;

uses(TestCase::class)->in('Feature');

use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\tests\fixtures\KeyPair;
use SAML2\Constants;
use SAML2\XML\ds\KeyInfo;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\md\EndpointType;
use SAML2\XML\md\EntityDescriptor;
use SAML2\XML\md\IDPSSODescriptor;
use SAML2\XML\md\KeyDescriptor;

/**
 * IdP metadata XML with an HTTP-POST SSO/SLO endpoint and the given signing certificates (each in its own
 * X509Data unless `$sameX509Data`).
 */
function idpMetadataXml(string $entityId, array $signingCertificates, bool $sameX509Data = false): string
{
    $idp = new IDPSSODescriptor;
    $idp->setProtocolSupportEnumeration([Constants::NS_SAMLP]);

    foreach (['setSingleSignOnService' => '/sso', 'setSingleLogoutService' => '/slo'] as $setter => $path) {
        $endpoint = new EndpointType;
        $endpoint->setBinding(Constants::BINDING_HTTP_POST);
        $endpoint->setLocation($entityId.$path);
        $idp->$setter([$endpoint]);
    }

    $keyInfo = new KeyInfo;
    $data = null;
    foreach ($signingCertificates as $pem) {
        if (! $sameX509Data || $data === null) {
            $data = new X509Data;
            $keyInfo->addInfo($data);
        }
        $certificate = new X509Certificate;
        $certificate->setCertificate(SecurityHelper::cleanCertificate($pem));
        $data->addData($certificate);
    }
    $keyDescriptor = new KeyDescriptor;
    $keyDescriptor->setUse('signing');
    $keyDescriptor->setKeyInfo($keyInfo);
    $idp->addKeyDescriptor($keyDescriptor);

    $entity = new EntityDescriptor;
    $entity->setEntityID($entityId);
    $entity->addRoleDescriptor($idp);

    return $entity->toXML()->ownerDocument->saveXML();
}

function keyPair(): KeyPair
{
    static $keyPair;

    return $keyPair ??= KeyPair::generate();
}
