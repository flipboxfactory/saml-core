<?php

declare(strict_types=1);

namespace flipbox\saml\core\models;

use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Url;

abstract class AbstractSettings extends PluginSettings implements SettingsInterface
{
    public const ENDPOINT_PREFIX = 'sso';

    /**
     * Which side of SAML this plugin is: {@see SettingsInterface::SP} or {@see SettingsInterface::IDP}. Set by the
     * plugin's settings class.
     */
    protected ?string $myType = null;

    /**
     * This system's entity ID. Environment variables and aliases are allowed, e.g. `$SAML_ENTITY_ID`. Defaults to
     * the site's base URL.
     */
    public ?string $entityId = null;

    /**
     * The first path segment of the SAML endpoints, e.g. `sso` for `/sso/login`. Environment variables are allowed.
     * Changing it changes the endpoint URLs in this system's metadata.
     */
    public string $endpointPrefix = self::ENDPOINT_PREFIX;

    /**
     * Whether an incoming (signature-verified) LogoutRequest ends every session of the user it names, even when the
     * request's browser isn't logged in.
     */
    public bool $sloDestroySpecifiedSessions = false;

    public function setEntityId(?string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId(): string
    {
        return (string) Env::parse($this->getEntityIdRaw());
    }

    public function getEntityIdRaw(): string
    {
        if (! $this->entityId) {
            $this->entityId = Url::baseSiteUrl();
        }

        return $this->entityId;
    }

    public function getEndpointPrefix(): string
    {
        return (string) Env::parse($this->endpointPrefix);
    }

    public function setMyType(string $myType): static
    {
        $this->myType = $myType;

        return $this;
    }

    public function getMyType(): ?string
    {
        return $this->myType;
    }

    public function getRemoteType(): string
    {
        return $this->getMyType() === self::IDP ? self::SP : self::IDP;
    }

    public function isIDP(): bool
    {
        return $this->getMyType() === self::IDP;
    }

    public function isSP(): bool
    {
        return $this->getMyType() === self::SP;
    }
}
