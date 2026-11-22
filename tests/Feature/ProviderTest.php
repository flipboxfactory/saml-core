<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\MetadataOptions;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\tests\fixtures\KeyPair;
use flipbox\saml\core\tests\fixtures\plugin\ProviderRecord;
use Illuminate\Support\Facades\DB;

function idpProvider(string $entityId = 'https://idp.example.com', array $certificates = []): ProviderRecord
{
    $provider = new ProviderRecord(['providerType' => 'idp', 'label' => 'Test IdP']);
    $provider->metadata = idpMetadataXml($entityId, $certificates ?: [keyPair()->certificatePem]);

    return test()->plugin()->getProvider()->save($provider);
}

it('saves a provider with its entity ID, hash and normalised metadata', function () {
    $provider = idpProvider();
    $row = DB::table(ProviderRecord::TABLE_ALIAS)->where('id', $provider->id)->first();

    expect($row->entityId)->toBe('https://idp.example.com')
        ->and($row->sha256)->toHaveLength(64)
        ->and($row->uid)->toMatch('/^[0-9a-f-]{36}$/')
        ->and($row->metadata)->toContain('https://idp.example.com/sso');
});

it('round-trips mapping, group and metadata options as JSON', function () {
    $provider = idpProvider();
    $provider->setMapping(['a' => ['attributeName' => 'mail', 'craftProperty' => 'email']]);
    $provider->setGroupOptions(new GroupOptions(['options' => ['sync' => ['1', '2'], 'allow' => [GroupOptions::ALLOW_ANY]]]));
    $provider->setMetadataOptions(new MetadataOptions(['url' => 'https://idp.example.com/metadata', 'expiryInterval' => 'P1D']));
    test()->plugin()->getProvider()->save($provider);

    $reloaded = ProviderRecord::find($provider->id);

    expect($reloaded->getMapping())->toBe([['attributeName' => 'mail', 'craftProperty' => 'email']])
        ->and($reloaded->getGroupOptions()->getSync())->toBe([1, 2])
        ->and($reloaded->getGroupOptions()->shouldAllowAny())->toBeTrue()
        ->and($reloaded->getMetadataOptions()->url)->toBe('https://idp.example.com/metadata')
        ->and($reloaded->getMetadataOptions()->getExpiryDate())->toBeInstanceOf(DateTimeInterface::class);
});

it('reads signing keys from every X509Data element in the metadata', function () {
    $other = KeyPair::generate();
    $provider = idpProvider(certificates: [keyPair()->certificatePem, $other->certificatePem]);

    expect(ProviderRecord::find($provider->id)->signingXMLSecurityKeyStore())->toHaveCount(2);
});

it('finds a signing certificate that isn’t first in its X509Data', function () {
    // Two certificates in one X509Data: both must be found
    $provider = idpProvider(certificates: [keyPair()->certificatePem, KeyPair::generate()->certificatePem]);
    $provider->metadata = idpMetadataXml('https://idp.example.com', [keyPair()->certificatePem, KeyPair::generate()->certificatePem], sameX509Data: true);

    expect($provider->signingXMLSecurityKey())->not->toBeNull()
        ->and($provider->signingXMLSecurityKeyStore())->toHaveCount(2);
});

it('fails closed on invalid metadata', function () {
    $provider = new ProviderRecord(['providerType' => 'idp']);
    $provider->metadata = '<not-metadata';

    $provider->getMetadataModel();
})->throws(Exception::class);

it('links a provider to its key pair by UID', function () {
    $keyPair = KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']);
    $provider = idpProvider();
    $provider->setKeychain($keyPair);
    test()->plugin()->getProvider()->save($provider);

    expect(ProviderRecord::find($provider->id)->keychain?->id)->toBe($keyPair->id)
        ->and(LinkRecord::query()->where('providerUid', $provider->uid)->value('keyChainId'))->toBe($keyPair->id);
});

it('doesn’t confuse key pairs of the other SAML plugin’s provider with the same ID', function () {
    $mine = KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']);
    $provider = idpProvider();

    // A link from the other plugin (e.g. saml-idp), whose provider has the same numeric ID
    $theirs = KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-idp']);
    LinkRecord::query()->create(['providerId' => $provider->id, 'providerUid' => (string) Str::uuid(), 'keyChainId' => $theirs->id]);

    expect(ProviderRecord::find($provider->id)->keychain)->toBeNull();

    $provider->setKeychain($mine);
    test()->plugin()->getProvider()->save($provider);

    expect(ProviderRecord::find($provider->id)->keychain->id)->toBe($mine->id);
});

it('signs with the linked private key and fails closed without one', function () {
    $provider = idpProvider();

    expect(fn () => $provider->keychainPrivateXmlSecurityKey())->toThrow(flipbox\saml\core\exceptions\InvalidMetadata::class);

    $provider->setKeychain(KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']));
    expect($provider->keychainPrivateXmlSecurityKey())->toBeInstanceOf(RobRichards\XMLSecLibs\XMLSecurityKey::class);
});

it('finds providers by type, enabled state and entity ID', function () {
    $idp = idpProvider();
    $disabled = idpProvider('https://other-idp.example.com');
    $disabled->enabled = false;
    $disabled->save();

    $service = test()->plugin()->getProvider();

    expect($service->findByIdp()->pluck('id')->all())->toBe([$idp->id])
        ->and($service->findBySp()->count())->toBe(0)
        ->and($service->findByEntityId('https://other-idp.example.com')->first()?->id)->toBe($disabled->id);
});

it('deletes a provider and its key pair link', function () {
    $provider = idpProvider();
    $provider->setKeychain(KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'saml-core-test']));
    test()->plugin()->getProvider()->save($provider);

    expect(test()->plugin()->getProvider()->delete($provider))->toBeTrue()
        ->and(LinkRecord::query()->where('providerUid', $provider->uid)->exists())->toBeFalse();
});
