<?php


namespace flipbox\saml\core\services;


use craft\base\Component;
use craft\elements\User;
use flipbox\saml\core\helpers\MappingHelper;

class Cp extends Component
{

    public function getMappingFieldOptions()
    {
        $user = new User();
        $options = [
            [
                'label' => $user->getAttributeLabel('firstName'),
                'value' => 'firstName',
            ],
            [
                'label' => $user->getAttributeLabel('lastName'),
                'value' => 'lastName',
            ],
            [
                'label' => $user->getAttributeLabel('email'),
                'value' => 'email',
            ],
            [
                'label' => $user->getAttributeLabel('username'),
                'value' => 'username',
            ],
            [
                'label' => $user->getAttributeLabel('uid'),
                'value' => 'uid',
            ],
            [
                'label' => $user->getAttributeLabel('id'),
                'value' => 'id',
            ],
        ];
        foreach ($user->getFieldLayout()->getFields() as $field) {
            if (MappingHelper::isSupportedField($field)) {
                $options[] = [
                    'label' => $field->name,
                    'value' => $field->handle,
                ];
            }
        }

        return $options;
    }

    public function getMappingAutoSuggestions()
    {
        $user = new User();
        $options = [
            [
                'hint' => $user->getAttributeLabel('firstName'),
                'name' => '{firstName}',
            ],
            [
                'hint' => $user->getAttributeLabel('lastName'),
                'name' => '{lastName}',
            ],
            [
                'hint' => $user->getAttributeLabel('email'),
                'name' => '{email}',
            ],
            [
                'hint' => $user->getAttributeLabel('username'),
                'name' => '{username}',
            ],
            [
                'hint' => $user->getAttributeLabel('uid'),
                'name' => '{uid}',
            ],
            [
                'hint' => $user->getAttributeLabel('id'),
                'name' => '{id}',
            ],
        ];

        $contentOptions = [];
        foreach ($user->getFieldLayout()->getFields() as $field) {
            if (! MappingHelper::isSupportedField($field)) {
                continue;
            }

            $fieldType = get_class($field);
            $contentOptions[] = [
                'hint' => $field->name . " ($fieldType)",
                'name' => '{' . $field->handle . '}',
            ];
        }

        $return = [
            [
                'label' => 'Standard Fields',
                'data' => $options,
            ],
        ];

        if (! empty($contentOptions)) {
            $return[] = [
                'label' => 'Content Fields',
                'data' => $contentOptions,
            ];
        }

        return $return;
    }

}