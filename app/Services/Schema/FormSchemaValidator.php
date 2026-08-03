<?php

namespace App\Services\Schema;

class FormSchemaValidator
{
    /**
     * Validate a schema array structurally. Returns ['valid' => bool, 'errors' => string[]].
     */
    public function validate(array $schema): array
    {
        $errors = [];

        if (! isset($schema['sections']) || ! is_array($schema['sections'])) {
            return ['valid' => false, 'errors' => ['schema.sections must be an array.']];
        }

        if (empty($schema['sections'])) {
            return ['valid' => true, 'errors' => []];
        }

        $keys = [];

        foreach ($schema['sections'] as $i => $section) {
            if (! isset($section['fields']) || ! is_array($section['fields'])) {
                $errors[] = "section[{$i}] must contain a fields array.";

                continue;
            }

            foreach ($section['fields'] as $j => $field) {
                $path = "sections[{$i}].fields[{$j}]";

                if (! isset($field['type']) || ! FieldTypeRegistry::exists($field['type'])) {
                    $errors[] = "{$path}: unknown or missing field type.".($field['type'] ?? '');

                    continue;
                }

                if (empty($field['label'])) {
                    $errors[] = "{$path}: label is required.";
                }

                if (empty($field['key'])) {
                    $errors[] = "{$path}: key is required.";
                } elseif (isset($keys[$field['key']])) {
                    $errors[] = "{$path}: duplicate key '{$field['key']}'.";
                }

                if ($field['key'] ?? null) {
                    $keys[$field['key']] = true;
                }

                if (FieldTypeRegistry::hasOptions($field['type'])) {
                    $options = $field['options'] ?? [];
                    if (! is_array($options) || count($options) < 1) {
                        $errors[] = "{$path}: '{$field['type']}' requires at least one option.";
                    } else {
                        foreach ($options as $k => $option) {
                            $value = is_array($option) ? ($option['value'] ?? null) : $option;
                            if (empty($value)) {
                                $errors[] = "{$path}: option at index {$k} has no value.";
                            }
                        }
                    }
                }

                if (FieldTypeRegistry::isInput($field['type']) && $field['type'] !== 'file') {
                    if ($this->hasInvalidLength($field['validation'] ?? [])) {
                        $errors[] = "{$path}: min_length cannot exceed max_length.";
                    }
                    if ($this->hasInvalidRange($field['validation'] ?? [])) {
                        $errors[] = "{$path}: min cannot exceed max.";
                    }
                }
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Normalize a schema: fill defaults, generate ids/keys, coerce option shapes.
     * Never throws; best-effort repair is the point.
     */
    public function normalize(array $schema): array
    {
        $schema['title'] = trim((string) ($schema['title'] ?? ''));
        $schema['description'] = trim((string) ($schema['description'] ?? ''));
        $schema['sections'] = $schema['sections'] ?? [];

        $usedKeys = [];

        foreach ($schema['sections'] as &$section) {
            $section['id'] = $section['id'] ?? 'sec_'.str()->random(8);
            $section['title'] = trim((string) ($section['title'] ?? 'Untitled section'));
            $section['fields'] = $section['fields'] ?? [];

            foreach ($section['fields'] as &$field) {
                $field = $this->normalizeField($field, $usedKeys);
            }
            unset($field);
        }
        unset($section);

        return $schema;
    }

    public function normalizeField(array $field, array &$usedKeys): array
    {
        $type = $field['type'] ?? 'text';

        if (! FieldTypeRegistry::exists($type)) {
            $type = 'text';
        }

        $field['id'] = $field['id'] ?? 'fld_'.str()->random(8);
        $field['type'] = $type;
        $field['label'] = trim((string) ($field['label'] ?? ucfirst($type)));

        $key = $field['key'] ?? null;
        $base = $key ? str($key)->lower()->slug('_')->toString() : str($field['label'])->lower()->slug('_')->toString();
        if ($base === '') {
            $base = $type;
        }
        $unique = $base;
        $i = 2;
        while (isset($usedKeys[$unique])) {
            $unique = $base.'_'.$i++;
        }
        $usedKeys[$unique] = true;
        $field['key'] = $unique;

        $field['placeholder'] = $field['placeholder'] ?? null;
        $field['help_text'] = $field['help_text'] ?? null;
        $field['default'] = $field['default'] ?? null;
        $field['required'] = (bool) ($field['required'] ?? false);

        $field['options'] = array_values(array_map(
            fn ($option) => is_array($option) ? ['label' => (string) ($option['label'] ?? $option['value'] ?? ''), 'value' => (string) ($option['value'] ?? $option['label'] ?? '')] : ['label' => (string) $option, 'value' => (string) $option],
            is_array($field['options'] ?? null) ? $field['options'] : []
        ));

        $field['validation'] = array_merge(FieldTypeRegistry::defaultConfig(), is_array($field['validation'] ?? null) ? $field['validation'] : []);
        $field['conditions'] = is_array($field['conditions'] ?? null) ? $field['conditions'] : [];

        return $field;
    }

    /**
     * Derive Laravel validation rules from a schema for the given submitted data.
     * Server-side validation is derived from the schema - never trust the browser.
     */
    public function rules(array $schema, ?array $data = null): array
    {
        $rules = [];
        $data = $data ?? [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (! FieldTypeRegistry::isInput($field['type']) || $field['type'] === 'section' || $field['type'] === 'paragraph') {
                    continue;
                }

                if (! $this->isVisible($field, $data)) {
                    continue;
                }

                $key = $field['key'];
                $fieldRules = [];

                if (($field['required'] ?? false)) {
                    $fieldRules[] = $field['type'] === 'checkbox' ? 'required' : 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }

                $fieldRules = $this->applyTypeRules($fieldRules, $field);
                $rules[$key] = $fieldRules;
            }
        }

        return $rules;
    }

    protected function applyTypeRules(array $fieldRules, array $field): array
    {
        $type = $field['type'];
        $v = $field['validation'] ?? [];

        if ($field['type'] === 'file') {
            $fieldRules[] = 'file';

            if (! empty($v['mimes'])) {
                $fieldRules[] = 'mimes:'.implode(',', array_map(fn ($m) => trim((string) $m), $v['mimes']));
            }

            if (! empty($v['max_size'])) {
                $fieldRules[] = 'max:'.(int) $v['max_size'];
            }

            return $fieldRules;
        }

        if ($field['type'] === 'checkbox') {
            $fieldRules[] = 'array';

            if (! empty($v['min_selections'])) {
                $fieldRules[] = 'min:'.(int) $v['min_selections'];
            }

            if (! empty($v['max_selections'])) {
                $fieldRules[] = 'max:'.(int) $v['max_selections'];
            }

            return $fieldRules;
        }

        if (! empty($v['min_length'])) {
            $fieldRules[] = 'min:'.(int) $v['min_length'];
        }

        if (! empty($v['max_length'])) {
            $fieldRules[] = 'max:'.(int) $v['max_length'];
        }

        if (! empty($v['pattern'])) {
            $fieldRules[] = 'regex:'.$v['pattern'];
        }

        if ($type === 'email') {
            $fieldRules[] = 'email';
        } elseif ($type === 'url') {
            $fieldRules[] = 'url';
        } elseif ($type === 'number') {
            $fieldRules[] = 'numeric';
            if ($v['min'] !== null && $v['min'] !== '') {
                $fieldRules[] = 'min:'.(float) $v['min'];
            }
            if ($v['max'] !== null && $v['max'] !== '') {
                $fieldRules[] = 'max:'.(float) $v['max'];
            }
        } elseif ($type === 'date') {
            $fieldRules[] = 'date';
            if ($v['min'] !== null && $v['min'] !== '') {
                $fieldRules[] = 'after_or_equal:'.date('Y-m-d', strtotime((string) $v['min']));
            }
            if ($v['max'] !== null && $v['max'] !== '') {
                $fieldRules[] = 'before_or_equal:'.date('Y-m-d', strtotime((string) $v['max']));
            }
        } elseif ($type === 'rating') {
            $fieldRules[] = 'integer|between:1,5';
        } elseif (in_array($type, ['dropdown', 'radio'], true)) {
            $fieldRules[] = 'in:'.implode(',', array_map(fn ($o) => $o['value'], $field['options'] ?? []));
        }

        return $fieldRules;
    }

    /**
     * Flatten all input fields across sections.
     */
    public function allFields(array $schema): array
    {
        $fields = [];

        foreach ($schema['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Conditions support (Part D): a field is visible only if all conditions match the submitted data.
     */
    public function isVisible(array $field, array $data): bool
    {
        $conditions = $field['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $target = $condition['field'] ?? null;
            $op = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (! $target) {
                continue;
            }

            $actual = $data[$target] ?? null;

            if (is_array($actual)) {
                $actual = implode(',', $actual);
            }

            $matches = match ($op) {
                'equals' => (string) $actual === (string) $value,
                'not_equals' => (string) $actual !== (string) $value,
                'contains' => is_string($actual) && str_contains($actual, (string) $value),
                'empty' => empty($actual),
                'not_empty' => ! empty($actual),
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    protected function hasInvalidLength(array $v): bool
    {
        return ($v['min_length'] ?? null) !== null
            && ($v['max_length'] ?? null) !== null
            && (int) $v['min_length'] > (int) $v['max_length'];
    }

    protected function hasInvalidRange(array $v): bool
    {
        return ($v['min'] ?? null) !== null
            && ($v['max'] ?? null) !== null
            && (float) $v['min'] > (float) $v['max'];
    }
}
