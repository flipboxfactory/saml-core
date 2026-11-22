<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\cp\view;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use flipbox\saml\core\EnsureSAMLPlugin;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

/**
 * Base for the SAML plugins' control panel screens. Routes require an admin.
 */
abstract class AbstractController implements EnsureSAMLPlugin
{
    /**
     * @param  list<ActionItem>  $crumbs
     */
    protected function screen(string $title, array $crumbs = []): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title($title)
            ->crumbs([
                new ActionItem()->label((string) $this->getPlugin()->name)->href(Url::cpUrl($this->getPlugin()->handle)),
                ...$crumbs,
            ]);
    }

    /**
     * A form screen that posts to one of the plugin's actions, with values and errors from a failed submission.
     *
     * @param  array<string, mixed>  $values
     */
    protected function form(CpScreenResponse $screen, Request $request, Ui $ui, array $values, string $action, ?string $redirect = null): CpScreenResponse
    {
        $errors = $request->session()->get('errors');

        return $screen
            ->redirectUrl($redirect)
            ->ui($ui, new UiContext(
                values: [...$values, ...array_intersect_key((array) $request->old(), $values)],
                errors: $errors instanceof ViewErrorBag ? $errors->getBag('default')->getMessages() : [],
            ), [
                'submit' => ['method' => 'post', 'url' => Url::actionUrl($this->getPlugin()->handle.'/'.$action)],
                ...($redirect ? ['redirect' => Url::cpUrl($redirect)] : []),
            ]);
    }
}
