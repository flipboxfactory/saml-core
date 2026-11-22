<?php

declare(strict_types=1);

namespace flipbox\saml\core\records;

use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\models\SettingsInterface;
use SAML2\XML\md\EntityDescriptor;

interface ProviderInterface
{
    public const TYPE_IDP = SettingsInterface::IDP;

    public const TYPE_SP = SettingsInterface::SP;

    public function getMetadataModel(): ?EntityDescriptor;

    public function setMetadataModel(EntityDescriptor $descriptor): static;

    public function getType(): ?string;

    public function getKeychain(): ?KeyChainRecord;

    public function setKeychain(?KeyChainRecord $keyChain): static;

    public function getEntityId(): ?string;

    /**
     * @return list<array<string, mixed>>
     */
    public function getMapping(): array;

    public function toXmlString(): string;
}
