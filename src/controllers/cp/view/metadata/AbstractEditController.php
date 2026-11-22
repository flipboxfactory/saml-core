<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\cp\view\metadata;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Nodes\CopyAttribute;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\models\GroupOptions;
use flipbox\saml\core\models\SettingsInterface;
use flipbox\saml\core\records\AbstractProvider;
use Illuminate\Http\Request;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Edits a provider: this system's own ("My Provider"), or a partner IdP/SP.
 */
abstract class AbstractEditController extends AbstractController
{
    public function index(Request $request, ?int $providerId = null): CpScreenResponse
    {
        if ($providerId === null) {
            return $this->edit($request, $this->newProvider($this->getPlugin()->getRemoteType()), $this->getPlugin()->getRemoteType());
        }

        $provider = $this->getPlugin()->getProvider()->find(['id' => $providerId])->firstOr(fn () => abort(404, 'Provider not found.'));

        return $this->edit($request, $provider, $this->createTypeFor($provider));
    }

    public function newIdp(Request $request): CpScreenResponse
    {
        return $this->edit($request, $this->newProvider(SettingsInterface::IDP), SettingsInterface::IDP);
    }

    public function newSp(Request $request): CpScreenResponse
    {
        return $this->edit($request, $this->newProvider(SettingsInterface::SP), SettingsInterface::SP);
    }

    public function myProvider(Request $request): CpScreenResponse
    {
        $provider = $this->getPlugin()->getProvider()->findOwn()
            ?? $this->newProvider((string) $this->getPlugin()->getMyType());

        return $this->edit($request, $provider, (string) $this->getPlugin()->getMyType());
    }

    protected function newProvider(string $type): AbstractProvider
    {
        $class = $this->getPlugin()->getProviderRecordClass();

        return new $class(['providerType' => $type, 'enabled' => true]);
    }

    /**
     * The provider type the screen edits: the plugin's own type for "My Provider", otherwise the provider's.
     */
    protected function createTypeFor(AbstractProvider $provider): string
    {
        $own = $this->getPlugin()->getProvider()->findOwn();

        return $own && $own->id === $provider->id ? (string) $this->getPlugin()->getMyType() : (string) $provider->providerType;
    }

    protected function edit(Request $request, AbstractProvider $provider, string $createType): CpScreenResponse
    {
        $plugin = $this->getPlugin();
        $handle = $plugin->handle;
        $myType = (string) $plugin->getMyType();
        $isOwn = $createType === $myType;
        $isRemote = ! $isOwn && $provider->providerType === $plugin->getRemoteType();
        $idpEditingSp = $myType === SettingsInterface::IDP && $provider->providerType === SettingsInterface::SP;

        $ui = Ui::make([
            HiddenField::make('providerType'),
            ...($provider->exists ? [HiddenField::make('identifier')] : []),
            Field::make(t('Label', category: $handle), Text::make('label'))
                ->instructions(t('Friendly name for the provider. You can use the provider’s name (like `OKTA`) or the environment (`DEV`) so everyone knows its purpose.', category: $handle))
                ->required(),
        ]);

        if ($isOwn) {
            $ui->add(
                Field::make(t('Entity ID', category: $handle), Text::make('entityId'))
                    ->instructions(t('This system’s entity ID. Defaults to the entity ID in the plugin settings.', category: $handle)),
                Field::make(t('Key Pair', category: $handle), Choice::make('keychain')->options($this->keyPairOptions())->placeholder(t('None')))
                    ->instructions(t('Changing the key pair means exchanging the new certificate with the other provider. Assigning a key pair is highly recommended.', category: $handle).' '
                        .'['.t('Manage key pairs', category: $handle).']('.Url::cpUrl("$handle/keychain").')'
                        .($provider->keychain ? ' · ['.t('Download certificate', category: $handle).']('.Url::actionUrl("$handle/metadata/download-certificate", ['keyId' => $provider->keychain->id]).')' : '')),
            );
        }

        if (Sites::isMultiSite()) {
            $ui->add(Field::make(t('Site'), Choice::make('providerSite')->options($this->siteOptions())->placeholder(t('Any', category: $handle)))
                ->instructions(t('The site whose base URL is used for this provider’s endpoints.', category: $handle)));
        }

        if ($idpEditingSp) {
            $ui->add(Field::make(t('Encrypt Assertions', category: $handle), Lightswitch::make('encryptAssertions')));
        }

        if ($provider->exists && $isRemote) {
            $ui->add(
                Separator::make('paths-separator'),
                Heading::make('paths-heading', t('Login/Logout Paths', category: $handle)),
                CopyAttribute::make('login-path', $myType === SettingsInterface::SP ? $provider->getLoginRequestPath() : $provider->getLoginRequestEndpoint()),
                CopyAttribute::make('logout-path', $myType === SettingsInterface::SP ? $provider->getLogoutRequestPath() : $provider->getLogoutRequestEndpoint()),
            );
        }

        if ($isRemote) {
            $this->addMappingUi($ui, $myType);
        }

        if ($idpEditingSp) {
            $this->addGroupOptionsUi($ui);
        }

        $ui->add(Separator::make('metadata-separator'), Heading::make('metadata-heading', t('Metadata', category: $handle)));

        if (! $isOwn) {
            $ui->add(
                Field::make(t('Metadata URL', category: $handle), Text::make('metadata-url-text'))
                    ->instructions(t('Fetch the provider’s metadata from this URL instead of pasting it.', category: $handle)),
            );
        }

        $ui->add(Field::make(t('Metadata XML', category: $handle), Textarea::make('metadata-text')->monospace()->rows(16))
            ->instructions($isOwn
                ? t('Share this XML with the other provider. It’s generated when you save.', category: $handle)
                : t('Paste the provider’s metadata XML. It’s overwritten when a metadata URL is set.', category: $handle))
            ->required(! $isOwn));

        $values = [
            'providerType' => $isOwn ? $myType : $provider->providerType,
            'identifier' => $provider->id,
            'label' => $provider->label,
            'entityId' => $isOwn ? ($provider->entityId ?: $plugin->getSettings()->getEntityId()) : $provider->entityId,
            'keychain' => $provider->keychain?->id,
            'providerSite' => $provider->siteId,
            'encryptAssertions' => (bool) $provider->encryptAssertions,
            'nameIdOverride' => $provider->nameIdOverride,
            'mapping' => $provider->getMapping(),
            'syncGroups' => (bool) $provider->syncGroups,
            'groupsAttributeName' => $provider->groupsAttributeName,
            'groupOptions' => $this->groupOptionRows($provider->getGroupOptions()),
            'metadata-url-text' => $provider->getMetadataOptions()->url,
            'metadata-text' => $this->metadataXml($provider),
        ];

        $title = $isOwn
            ? t('My Provider', category: $handle)
            : ($provider->exists ? ($provider->label ?: (string) $provider->entityId) : t('New {type}', ['type' => strtoupper((string) $provider->providerType)], $handle));

        return $this->form(
            $this->screen($title, [
                new ActionItem()->label(t('Provider List', category: $handle))->href(Url::cpUrl("$handle/metadata")),
                new ActionItem()->label($title),
            ])->selectedSubnavItem($isOwn ? 'saml.myProvider' : 'saml.providers'),
            $request,
            $ui,
            $values,
            $isOwn ? 'metadata/auto-create' : 'metadata/save',
            "$handle/metadata",
        );
    }

    protected function addMappingUi(Ui $ui, string $myType): void
    {
        $handle = $this->getPlugin()->handle;
        $isIdp = $myType === SettingsInterface::IDP;

        $columns = [
            'attributeName' => ['heading' => t('Attribute Name', category: $handle), 'type' => 'singleline'],
            'craftProperty' => ['heading' => t('Craft User Property', category: $handle), 'type' => 'select', 'options' => $this->getPlugin()->getCp()->getMappingFieldOptions($isIdp)],
            ...($isIdp ? ['templateOverride' => ['heading' => t('Template Override', category: $handle), 'type' => 'singleline', 'code' => true]] : []),
        ];

        $ui->add(
            Separator::make('mapping-separator'),
            Heading::make('mapping-heading', t('User Attribute Mapping', category: $handle)),
            Field::make(t('NameID Override', category: $handle), Text::make('nameIdOverride')->placeholder($isIdp ? '{username}' : null))
                ->instructions($isIdp
                    ? t('The NameID is the unique identifier the Service Provider uses for the user, like a username. Build it from Craft user properties with an object template, e.g. `{email}`.', category: $handle)
                    : t('By default, the NameID is used as the Craft user’s username. To use a different value, enter the name of an assertion attribute sent in the SAML Response.', category: $handle)),
            Field::make(t('Attribute Mapping', category: $handle), Table::make('mapping')
                ->columns($columns)
                ->addRowLabel(t('Add an attribute', category: $handle))
                ->allowAdd()
                ->allowDelete()
                ->allowReorder()),
        );
    }

    protected function addGroupOptionsUi(Ui $ui): void
    {
        $handle = $this->getPlugin()->handle;

        $ui->add(
            Separator::make('groups-separator'),
            Heading::make('groups-heading', t('User Groups', category: $handle)),
            Field::make(t('Sync User Groups', category: $handle), Lightswitch::make('syncGroups')),
            Field::make(t('User Groups Attribute Name', category: $handle), Text::make('groupsAttributeName')->monospace()),
            MarkdownContent::make('groups-help', t('Users in any group switched on under **Allow Access** can sign in to this Service Provider. **Allow Any** allows every user, and **No Group Assigned** covers users who aren’t in a user group. Switch on **Sync Group** for each group to send to the Service Provider.', category: $handle)),
            Field::make(t('Access Control', category: $handle), Table::make('groupOptions')
                ->keyed()
                ->columns([
                    'name' => ['heading' => t('Group', category: $handle), 'type' => 'heading'],
                    'allow' => ['heading' => t('Allow Access', category: $handle), 'type' => 'lightswitch', 'class' => 'thin centered'],
                    'sync' => ['heading' => t('Sync Group', category: $handle), 'type' => 'lightswitch', 'class' => 'thin centered'],
                ])),
        );
    }

    /**
     * Rows for the group options table, keyed by group ID (or `allowany` / `nogroup`).
     *
     * @return array<string, array{name: string, allow: bool, sync: bool}>
     */
    protected function groupOptionRows(GroupOptions $options): array
    {
        $handle = $this->getPlugin()->handle;
        $rows = [
            GroupOptions::ALLOW_ANY => ['name' => t('Allow Any', category: $handle), 'allow' => $options->shouldAllow(GroupOptions::ALLOW_ANY), 'sync' => false],
            GroupOptions::NO_GROUP => ['name' => t('No Group Assigned', category: $handle), 'allow' => $options->shouldAllow(GroupOptions::NO_GROUP), 'sync' => false],
        ];

        foreach (UserGroups::getAllGroups() as $group) {
            $rows[(string) $group->id] = ['name' => (string) $group->name, 'allow' => $options->shouldAllow($group->id), 'sync' => $options->shouldSync($group->id)];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    protected function keyPairOptions(): array
    {
        return KeyChain::getInstance()->getService()->findByPlugin($this->getPlugin())->orderBy('id')->get()
            ->map(fn (KeyChainRecord $keyPair) => ['label' => (string) ($keyPair->description ?: t('Key pair', category: 'keychain')), 'value' => $keyPair->id])
            ->values()->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    protected function siteOptions(): array
    {
        $options = [];
        foreach (Sites::getAllSites() as $site) {
            $options[] = ['label' => (string) $site->name, 'value' => (int) $site->id];
        }

        return $options;
    }

    protected function metadataXml(AbstractProvider $provider): string
    {
        try {
            return $provider->metadata ? $provider->toXmlString() : '';
        } catch (Throwable) {
            // Show what's stored, even if it no longer parses
            return (string) $provider->metadata;
        }
    }
}
