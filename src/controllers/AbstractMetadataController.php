<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers;

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Url;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\exceptions\InvalidMetadata;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\MetadataOptions;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Provider form actions (`actions/{handle}/metadata/…`).
 */
abstract class AbstractMetadataController extends AbstractController
{
    /**
     * This system's metadata XML.
     */
    public function index(): HttpResponse
    {
        $provider = $this->getPlugin()->getProvider()->findByEntityId($this->getPlugin()->getSettings()->getEntityId())->first()
            ?? throw new InvalidMetadata('Metadata for this server is missing. Please configure this plugin.');

        return SerializeHelper::xmlResponse($provider->toXmlString());
    }

    /**
     * Saves a provider.
     */
    public function save(Request $request): Response
    {
        [$record, $errors] = $this->processSaveAction($request);

        if ($errors->isNotEmpty()) {
            return $this->failure($request, $errors);
        }

        $this->getPlugin()->getProvider()->save($record);

        return $this->asSuccess(t('Provider saved.', category: $this->getPlugin()->handle), redirect: $this->getPostedRedirectUrl() ?? $this->providerListUrl());
    }

    /**
     * Saves this system's own provider, generating its metadata from the plugin settings and key pair.
     */
    public function autoCreate(Request $request): Response
    {
        [$record, $errors] = $this->processSaveAction($request, own: true);

        if ($errors->isNotEmpty()) {
            return $this->failure($request, $errors);
        }

        $record->generateUid();
        $entityDescriptor = $this->getPlugin()->getMetadata()->create($this->getPlugin()->getSettings(), $record);

        $record->entityId = (string) $entityDescriptor->getEntityID();
        $record->setMetadataModel($entityDescriptor);
        $record->metadata = $record->toXmlString();

        $this->getPlugin()->getProvider()->save($record);

        return $this->asSuccess(t('Provider saved.', category: $this->getPlugin()->handle), redirect: $this->getPostedRedirectUrl() ?? $this->providerListUrl());
    }

    public function changeStatus(Request $request): Response
    {
        $record = $this->findProvider($request->input('identifier'));
        $record->enabled = ! $record->enabled;
        $this->getPlugin()->getProvider()->save($record);

        return $this->asSuccess(t('Provider saved.', category: $this->getPlugin()->handle));
    }

    public function delete(Request $request): Response
    {
        $record = $this->findProvider($request->input('identifier', $request->input('id')));

        if (! $this->getPlugin()->getProvider()->delete($record)) {
            return $this->asFailure(t('Couldn’t delete the provider.', category: $this->getPlugin()->handle));
        }

        return $this->asSuccess(t('Provider deleted.', category: $this->getPlugin()->handle), redirect: $this->providerListUrl());
    }

    /**
     * Downloads a key pair's certificate (public).
     */
    public function downloadCertificate(Request $request): HttpResponse
    {
        $keyId = $request->input('keyId');
        abort_unless(is_numeric($keyId), 404, 'Key not found.');

        $keychain = KeyChainRecord::query()->whereKey((int) $keyId)->firstOr(fn () => abort(404, 'Key not found.'));

        return new HttpResponse($keychain->getDecryptedCertificate(), 200, [
            'Content-Type' => 'application/x-x509-ca-cert',
            'Content-Disposition' => 'attachment; filename="certificate.crt"',
        ]);
    }

    /**
     * Builds the provider record from the submitted form.
     *
     * @return array{0: AbstractProvider, 1: MessageBag}
     */
    protected function processSaveAction(Request $request, bool $own = false): array
    {
        $plugin = $this->getPlugin();
        $errors = new MessageBag;
        $handle = $plugin->handle;

        $record = $request->filled('identifier')
            ? $this->findProvider($request->input('identifier'))
            : tap(new ($plugin->getProviderRecordClass()), fn (AbstractProvider $r) => $r->enabled = true);

        $providerType = (string) $request->input('providerType', $own ? $plugin->getMyType() : $plugin->getRemoteType());
        if (! in_array($providerType, [SettingsInterface::SP, SettingsInterface::IDP], true)) {
            $errors->add('providerType', t('Invalid provider type.', category: $handle));
        }

        $record->providerType = $providerType;
        $record->label = $request->string('label')->trim()->toString() ?: null;
        $record->nameIdOverride = $request->input('nameIdOverride') ?: null;
        $record->syncGroups = $request->boolean('syncGroups');
        $record->groupsAttributeName = $request->input('groupsAttributeName') ?: AbstractProvider::DEFAULT_GROUPS_ATTRIBUTE_NAME;

        if (! $record->label) {
            $errors->add('label', t('Label is required.', category: $handle));
        }

        if ($request->filled('entityId')) {
            $record->entityId = (string) $request->input('entityId');
        }

        if ($siteId = $request->input('providerSite')) {
            $site = Site::query()->whereKey((int) $siteId)->first();
            $site ? $record->setSite($site) : $errors->add('providerSite', t('The site doesn’t exist.', category: $handle));
        } else {
            $record->setSite(null);
        }

        // Metadata: pasted XML, or fetched from a URL
        $metadataUrl = (string) $request->input('metadata-url-text', '');
        $metadata = (string) $request->input('metadata-text', '');

        if (! $own) {
            try {
                if ($metadata === '' && $metadataUrl !== '') {
                    $record->setMetadataModel($plugin->getMetadata()->fetchByUrl($metadataUrl));
                    $record->metadata = $record->toXmlString();
                } elseif ($metadata !== '') {
                    $record->setMetadataXml($metadata);
                    $record->getMetadataModel();
                }
            } catch (Throwable $e) {
                $errors->add('metadata-text', t('The metadata isn’t valid: {error}', ['error' => $e->getMessage()], $handle));
            }

            if ($plugin->getRemoteType() === $providerType && ! $record->metadata) {
                $errors->add('metadata-text', t('Metadata cannot be empty.', category: $handle));
            }
        }

        if (is_array($mapping = $request->input('mapping'))) {
            $record->setMapping(array_values(array_filter($mapping, 'is_array')));
        }

        if ($plugin->getMyType() === SettingsInterface::IDP && $providerType === SettingsInterface::SP) {
            $record->encryptAssertions = $request->boolean('encryptAssertions');
            $record->encryptionMethod = $request->input('encryptionMethod') ?: null;
            $record->setGroupOptions(new GroupOptions(['options' => $this->groupOptionsFromInput((array) $request->input('groupOptions', []))]));
        }

        $record->setMetadataOptions(new MetadataOptions([
            'url' => $metadataUrl ?: null,
            'expiryInterval' => $request->input('metadata-url-interval-text') ?: null,
        ]));

        if ($keyId = $request->input('keychain')) {
            $keychain = KeyChainRecord::query()->whereKey((int) $keyId)->first();
            $keychain ? $record->setKeychain($keychain) : $errors->add('keychain', t('The key pair doesn’t exist.', category: $handle));
        }

        return [$record, $errors];
    }

    /**
     * Group options from the access control table (`{groupId|allowany|nogroup: {allow, sync}}`), or the 5.x form
     * shape (`{allow: [...], sync: [...]}`).
     *
     * @param  array<array-key, mixed>  $input
     * @return array{allow: list<int|string>, sync: list<int|string>}
     */
    protected function groupOptionsFromInput(array $input): array
    {
        if (isset($input[GroupOptions::OPTION_ALLOW]) || isset($input[GroupOptions::OPTION_SYNC])) {
            return [
                'allow' => array_values((array) ($input[GroupOptions::OPTION_ALLOW] ?? [])),
                'sync' => array_values((array) ($input[GroupOptions::OPTION_SYNC] ?? [])),
            ];
        }

        $options = ['allow' => [], 'sync' => []];
        foreach ($input as $id => $row) {
            if (! is_array($row) || ! (is_numeric($id) || in_array($id, [GroupOptions::ALLOW_ANY, GroupOptions::NO_GROUP], true))) {
                continue;
            }
            foreach (['allow', 'sync'] as $option) {
                if (filter_var($row[$option] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $options[$option][] = $id;
                }
            }
        }

        return $options;
    }

    protected function findProvider(mixed $id): AbstractProvider
    {
        abort_unless(is_numeric($id), 404, 'Provider not found.');

        return $this->getPlugin()->getProvider()->find(['id' => (int) $id])->firstOr(fn () => abort(404, 'Provider not found.'));
    }

    protected function failure(Request $request, MessageBag $errors): Response
    {
        if ($request->expectsJson()) {
            return $this->asJsonFailure(t('Couldn’t save the provider.', category: $this->getPlugin()->handle), ['errors' => $errors->getMessages()]);
        }

        $request->flash();

        return back()->withErrors($errors);
    }

    protected function providerListUrl(): string
    {
        return Url::cpUrl($this->getPlugin()->handle.'/metadata');
    }
}
