<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\cp\view\metadata;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use flipbox\saml\core\controllers\cp\view\AbstractController;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;

use function CraftCms\Cms\t;

/**
 * The provider list.
 */
abstract class AbstractDefaultController extends AbstractController
{
    public function index(): CpScreenResponse
    {
        $handle = $this->getPlugin()->handle;
        $own = $this->getPlugin()->getProvider()->findOwn();

        $nodes = [];
        foreach ([ProviderInterface::TYPE_SP => t('Service Providers', category: $handle), ProviderInterface::TYPE_IDP => t('Identity Providers', category: $handle)] as $type => $heading) {
            $providers = $this->getPlugin()->getProvider()->find(['providerType' => $type])->orderBy('id')->get();

            $nodes[] = Heading::make("providers-$type-heading", $heading);
            $nodes[] = Table::make("providers-$type")
                ->columns([
                    ['key' => 'label', 'label' => t('Label', category: $handle), 'sortable' => true, 'width' => '1.5fr'],
                    ['key' => 'entityId', 'label' => t('Entity ID', category: $handle)],
                    ['key' => 'uid', 'label' => t('UID')],
                    ['key' => 'dateUpdated', 'label' => t('Date Updated')],
                ])
                ->rows($providers->map(function (AbstractProvider $provider) use ($own, $handle) {
                    $isMine = $own && $own->id === $provider->id;

                    return [
                        'id' => $provider->id,
                        '_status' => $provider->isEnabled(),
                        'label' => [
                            'label' => ($provider->label ?: t('Provider', category: $handle)).($isMine ? ' ('.t('My Provider', category: $handle).')' : ''),
                            'url' => Url::cpUrl("$handle/metadata/".($isMine ? 'my-provider' : $provider->id)),
                        ],
                        'entityId' => $provider->entityId,
                        'uid' => $provider->uid,
                        'dateUpdated' => ['date' => $provider->dateUpdated?->toIso8601String()],
                    ];
                }))
                ->emptyMessage($type === ProviderInterface::TYPE_SP ? t('No service providers exist yet.', category: $handle) : t('No identity providers exist yet.', category: $handle))
                ->createAction(
                    $type === ProviderInterface::TYPE_SP ? t('New service provider', category: $handle) : t('New identity provider', category: $handle),
                    Url::cpUrl("$handle/metadata/new-$type"),
                )
                ->deletable(Url::actionUrl("$handle/metadata/delete"), t('Are you sure you want to delete this provider? SSO with it will stop working.', category: $handle));
        }

        return $this->screen(t('Provider List', category: $handle), [new ActionItem()->label(t('Provider List', category: $handle))])
            ->selectedSubnavItem('saml.providers')
            ->ui(Ui::make($nodes));
    }
}
