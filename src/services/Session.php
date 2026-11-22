<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

/**
 * SAML state kept in the user's session, namespaced per plugin.
 */
class Session extends PluginService
{
    public const CORE_NAMESPACE = 'saml.core';

    public const REQUEST_ID_KEY = 'request.id';

    public const HASH_ALGO = 'sha256';

    /**
     * Remembers the ID of a request we sent, to check a response's InResponseTo against.
     */
    public function setRequestId(?string $id): void
    {
        session()->put($this->getName(static::REQUEST_ID_KEY), $id);
    }

    public function getRequestId(): ?string
    {
        $id = session()->get($this->getName(static::REQUEST_ID_KEY));

        return is_string($id) ? $id : null;
    }

    /**
     * A hash of the session ID, safe to store (e.g. as a SAML SessionIndex).
     */
    public function getId(): string
    {
        return hash(static::HASH_ALGO, session()->getId());
    }

    protected function getName(string $key): string
    {
        return static::CORE_NAMESPACE.'/'.$this->getPlugin()->handle.'/'.$key;
    }
}
