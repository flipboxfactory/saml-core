<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Plugins;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * Saves the settings page (`actions/{handle}/settings/save`).
 *
 * Only the posted settings change: Craft keeps the plugin's other stored settings, and doesn't copy values set in
 * `config/craft/{handle}.php` into project config.
 */
abstract class AbstractSettingsController extends AbstractController
{
    public function save(Request $request): Response
    {
        abort_unless(Cms::config()->allowAdminChanges, 403, 'Administrative changes are disallowed in this environment.');

        $validated = $request->validate([
            'entityId' => ['required', 'string', 'max:255'],
            'endpointPrefix' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-\/$@{}.]+$/'],
        ]);

        $plugin = $this->getPlugin();

        if (! Plugins::savePluginSettings($plugin, $validated)) {
            return $this->asFailure(t('Couldn’t save settings.', category: $plugin->handle), [
                'errors' => $plugin->getSettings()->errors()->getMessages(),
            ]);
        }

        return $this->asSuccess(t('Settings saved.', category: $plugin->handle));
    }
}
