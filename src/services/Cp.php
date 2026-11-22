<?php

declare(strict_types=1);

namespace flipbox\saml\core\services;

use CraftCms\Cms\Support\Facades\Users;
use flipbox\saml\core\helpers\MappingHelper;

use function CraftCms\Cms\t;

/**
 * Options for the control panel's attribute mapping.
 */
class Cp extends PluginService
{
    /**
     * @return list<array{label: string, value: string}>
     */
    public function getMappingFieldOptions(bool $isIdp = true): array
    {
        $options = [
            ['label' => t('First Name'), 'value' => 'firstName'],
            ['label' => t('Last Name'), 'value' => 'lastName'],
            ['label' => t('Email'), 'value' => 'email'],
            ['label' => t('Username'), 'value' => 'username'],
        ];

        if ($isIdp) {
            array_unshift($options, ['label' => 'N/A (using override)', 'value' => '']);
            $options[] = ['label' => t('UID'), 'value' => 'uid'];
            $options[] = ['label' => t('ID'), 'value' => 'id'];
        }

        foreach ($this->customFields() as $field) {
            $options[] = ['label' => (string) $field->name, 'value' => (string) $field->handle];
        }

        return $options;
    }

    /**
     * @return list<array{label: string, data: list<array{hint: string, name: string}>}>
     */
    public function getMappingAutoSuggestions(): array
    {
        $standard = [];
        foreach (['firstName' => 'First Name', 'lastName' => 'Last Name', 'email' => 'Email', 'username' => 'Username', 'uid' => 'UID', 'id' => 'ID'] as $attribute => $label) {
            $standard[] = ['hint' => t($label), 'name' => '{'.$attribute.'}'];
        }

        $content = [];
        foreach ($this->customFields() as $field) {
            $content[] = ['hint' => $field->name.' ('.$field::class.')', 'name' => '{'.$field->handle.'}'];
        }

        return [
            ['label' => 'Standard Fields', 'data' => $standard],
            ...($content === [] ? [] : [['label' => 'Content Fields', 'data' => $content]]),
        ];
    }

    /**
     * @return list<object{name: string, handle: string}>
     */
    private function customFields(): array
    {
        $fields = Users::getFieldLayout()->getCustomFields();

        return array_values(array_filter($fields, MappingHelper::isSupportedField(...)));
    }
}
