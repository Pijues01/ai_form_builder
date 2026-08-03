<?php

namespace App\Services\Schema;

class FieldTypeRegistry
{
    public const TYPES = [
        'text' => [
            'label' => 'Text',
            'input' => true,
            'has_options' => false,
        ],
        'textarea' => [
            'label' => 'Paragraph',
            'input' => true,
            'has_options' => false,
        ],
        'number' => [
            'label' => 'Number',
            'input' => true,
            'has_options' => false,
        ],
        'email' => [
            'label' => 'Email',
            'input' => true,
            'has_options' => false,
        ],
        'phone' => [
            'label' => 'Phone',
            'input' => true,
            'has_options' => false,
        ],
        'url' => [
            'label' => 'URL',
            'input' => true,
            'has_options' => false,
        ],
        'date' => [
            'label' => 'Date',
            'input' => true,
            'has_options' => false,
        ],
        'time' => [
            'label' => 'Time',
            'input' => true,
            'has_options' => false,
        ],
        'dropdown' => [
            'label' => 'Dropdown',
            'input' => true,
            'has_options' => true,
        ],
        'radio' => [
            'label' => 'Radio',
            'input' => true,
            'has_options' => true,
        ],
        'checkbox' => [
            'label' => 'Checkboxes',
            'input' => true,
            'has_options' => true,
        ],
        'file' => [
            'label' => 'File Upload',
            'input' => true,
            'has_options' => false,
        ],
        'rating' => [
            'label' => 'Rating',
            'input' => true,
            'has_options' => false,
        ],
        'section' => [
            'label' => 'Section Heading',
            'input' => false,
            'has_options' => false,
        ],
        'paragraph' => [
            'label' => 'Info / Paragraph',
            'input' => false,
            'has_options' => false,
        ],
    ];

    public static function all(): array
    {
        return self::TYPES;
    }

    public static function inputTypes(): array
    {
        return array_filter(self::TYPES, fn ($t) => $t['input']);
    }

    public static function exists(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function isInput(string $type): bool
    {
        return self::exists($type) && self::TYPES[$type]['input'];
    }

    public static function hasOptions(string $type): bool
    {
        return self::exists($type) && self::TYPES[$type]['has_options'];
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? ucfirst($type);
    }

    public static function defaultConfig(): array
    {
        return [
            'min' => null,
            'max' => null,
            'min_length' => null,
            'max_length' => null,
            'step' => null,
            'pattern' => null,
            'min_selections' => null,
            'max_selections' => null,
            'mimes' => [],
            'max_size' => null,
            'max_files' => null,
        ];
    }

    public static function newField(string $type, ?string $label = null): array
    {
        $label = $label ?: ucfirst($type);

        return [
            'id' => 'fld_'.str()->random(8),
            'type' => $type,
            'label' => $label,
            'key' => str($label)->lower()->slug('_')->toString(),
            'placeholder' => null,
            'help_text' => null,
            'default' => null,
            'required' => false,
            'options' => [],
            'validation' => self::defaultConfig(),
            'conditions' => [],
        ];
    }

    public static function newSection(?string $title = null): array
    {
        return [
            'id' => 'sec_'.str()->random(8),
            'title' => $title ?: 'Untitled section',
            'fields' => [],
        ];
    }
}
