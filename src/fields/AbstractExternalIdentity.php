<?php

declare(strict_types=1);

namespace flipbox\saml\core\fields;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\User\Elements\User;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\records\AbstractProviderIdentity;
use Illuminate\Database\Eloquent\Builder;

use function CraftCms\Cms\t;

/**
 * A read-only user field listing the user's identities (NameIDs) at the plugin's providers. Stores nothing.
 */
abstract class AbstractExternalIdentity extends Field implements EnsureSAMLPlugin
{
    public static function displayName(): string
    {
        return 'SAML External Identity';
    }

    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * @return Builder<AbstractProviderIdentity>|null
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if (! $element instanceof User || ! $element->id) {
            return null;
        }

        return $this->getPlugin()->getProviderIdentity()->findByUser($element);
    }

    public function uiControl(FieldContext $context): Control
    {
        $rows = [];
        if ($context->value instanceof Builder) {
            /** @var AbstractProviderIdentity $identity */
            foreach ($context->value->with('provider')->get() as $identity) {
                $rows[] = [
                    'provider' => (string) ($identity->getProvider()?->label ?: $identity->getProvider()?->entityId),
                    'nameId' => $identity->nameId,
                    'lastLoginDate' => $identity->lastLoginDate->toDateTimeString(),
                ];
            }
        }

        return Table::make($context->path)
            ->columns([
                'provider' => ['heading' => t('Provider', category: $this->getPlugin()->handle), 'type' => 'heading'],
                'nameId' => ['heading' => t('NameID', category: $this->getPlugin()->handle), 'type' => 'heading'],
                'lastLoginDate' => ['heading' => t('Last Login', category: $this->getPlugin()->handle), 'type' => 'heading'],
            ])
            ->value($rows)
            ->mode(ControlMode::ReadOnly);
    }
}
