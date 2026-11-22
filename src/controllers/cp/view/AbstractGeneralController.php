<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\cp\view;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Ui;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

/**
 * The plugin's Setup and Settings screens.
 */
abstract class AbstractGeneralController extends AbstractController
{
    public function setup(): CpScreenResponse
    {
        $plugin = $this->getPlugin();
        $handle = $plugin->handle;
        $remoteType = $plugin->getRemoteType();
        $own = $plugin->getProvider()->findOwn();

        $steps = implode("\n", [
            '1. ['.t('Create your metadata', category: $handle).']('.Url::cpUrl("$handle/metadata/my-provider").')',
            '2. ['.t('Install the {type}’s metadata', ['type' => strtoupper($remoteType)], $handle).']('.Url::cpUrl("$handle/metadata/new-$remoteType").')',
        ]);

        return $this->screen(t('Setup', category: $handle))
            ->selectedSubnavItem('saml.setup')
            ->ui(Ui::make([
                MarkdownContent::make('setup-steps', '## '.t('Getting Started', category: $handle)."\n\n".$steps),
                ...($own ? [MarkdownContent::make('setup-entity-id', t('Entity ID', category: $handle).': `'.$own->entityId.'`')] : []),
            ]));
    }

    public function settings(Request $request): CpScreenResponse
    {
        abort_unless(Cms::config()->allowAdminChanges, 403);

        $plugin = $this->getPlugin();
        $handle = $plugin->handle;
        $settings = $plugin->getSettings();
        $warning = t('If you change this, you must recreate “My Provider” and exchange the metadata with the other provider (IdP or SP).', category: $handle);

        $ui = Ui::make([
            Callout::make('settings-warning', $warning)->variant('warning'),
            Field::make(t('Default Entity ID', category: $handle), Text::make('entityId'))
                ->instructions(t('The unique ID for the site. Environment variables and aliases are allowed.', category: $handle))
                ->required(),
            Field::make(t('SSO Endpoint Prefix', category: $handle), Text::make('endpointPrefix')->monospace())
                ->instructions(t('The URL prefix for the SSO endpoints, e.g. `sso` for `/sso/login`.', category: $handle))
                ->required(),
        ]);

        return $this->form(
            $this->screen(t('Settings', category: $handle), [new ActionItem()->label(t('Settings', category: $handle))])->selectedSubnavItem('saml.settings'),
            $request,
            $ui,
            ['entityId' => $settings->getEntityIdRaw(), 'endpointPrefix' => $settings->endpointPrefix],
            'settings/save',
            "$handle/settings",
        );
    }
}
