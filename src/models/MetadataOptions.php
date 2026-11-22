<?php

declare(strict_types=1);

namespace flipbox\saml\core\models;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Where a provider's metadata is fetched from, and how often it's refreshed.
 */
class MetadataOptions implements \JsonSerializable
{
    use JsonModel;

    public ?string $url = null;

    /**
     * An ISO 8601 duration, e.g. `P1D`.
     */
    protected ?string $expiryInterval = null;

    protected ?DateTimeInterface $expiryDate = null;

    public function setExpiryInterval(?string $expiry): static
    {
        if (! $expiry) {
            $this->expiryInterval = null;
            $this->expiryDate = null;

            return $this;
        }

        $this->expiryInterval = $expiry;
        $this->expiryDate ??= (new DateTime)->add(new DateInterval($expiry));

        return $this;
    }

    public function getExpiryInterval(): ?string
    {
        return $this->expiryInterval;
    }

    public function getExpiryDate(): ?DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?string $expiry): static
    {
        if ($expiry) {
            $this->expiryDate = new DateTime($expiry);
        }

        return $this;
    }

    /**
     * @return array{expiryDate: ?string, expiryInterval: ?string, url: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'expiryDate' => $this->expiryDate?->format(DateTimeInterface::ATOM),
            'expiryInterval' => $this->expiryInterval,
            'url' => $this->url,
        ];
    }
}
