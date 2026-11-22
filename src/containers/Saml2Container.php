<?php

declare(strict_types=1);

namespace flipbox\saml\core\containers;

use CraftCms\Cms\View\TemplateMode;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SerializeHelper;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use SAML2\Compat\AbstractContainer;

use function CraftCms\Cms\template;

/**
 * Connects simplesamlphp/saml2 to Craft.
 *
 * saml2's bindings "send" a message by calling {@see redirect()} or {@see postRedirect()} and expect the request to
 * end there. Laravel can't end a request mid-stack, so both throw an {@see HttpResponseException} carrying the
 * response, which Laravel returns as the request's response.
 */
class Saml2Container extends AbstractContainer implements EnsureSAMLPlugin
{
    public const TEMPLATE_PATH = 'saml-core/_components/post-binding-submit.twig';

    /**
     * Maximum size of an inflated HTTP-Redirect message (saml2 4.19.4+ requires a limit).
     */
    public const INFLATE_SIZE_LIMIT = 8 * 1024 * 1024;

    protected LoggerInterface $logger;

    public function __construct(protected AbstractPlugin $plugin)
    {
        $this->logger = new Logger;
    }

    public function getPlugin(): AbstractPlugin
    {
        return $this->plugin;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function generateId(): string
    {
        return MessageHelper::generateId();
    }

    public function debugMessage($message, string $type): void
    {
        if ($message instanceof \DOMElement) {
            $message = (string) $message->ownerDocument?->saveXML($message);
        }

        $this->getLogger()->debug($message, ['type' => $type]);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws HttpResponseException always
     */
    public function redirect(string $url, array $data = []): void
    {
        throw new HttpResponseException(new RedirectResponse(SerializeHelper::redirectUrl($url, $data)));
    }

    /**
     * Sends an auto-submitting form that POSTs the message to `$url` (the HTTP-POST binding).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws HttpResponseException always
     */
    public function postRedirect(string $url, array $data = []): void
    {
        $data['destination'] = $url;
        $data['RelayState'] ??= '';

        throw new HttpResponseException(new Response(template($this->getTemplatePath(), $data, TemplateMode::Cp), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            // The form carries a signed message for a single use
            'Cache-Control' => 'no-store, private',
        ]));
    }

    public function getTempDir(): string
    {
        $tempDir = storage_path('saml2');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0770, true);
        }

        return $tempDir;
    }

    public function writeFile(string $filename, string $data, ?int $mode = null): void
    {
        file_put_contents($filename, $data);
        chmod($filename, $mode ?? 0600);
    }

    public function getInflateSizeLimit(): int
    {
        return self::INFLATE_SIZE_LIMIT;
    }

    protected function getTemplatePath(): string
    {
        return static::TEMPLATE_PATH;
    }
}
