<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use Livewire\Component;

class FormBuilder extends Component
{
    public ?int $formId = null;

    public string $title = '';

    public string $description = '';

    public array $sections = [];

    public bool $published = false;

    public ?array $selectedField = null;

    public ?array $selectedPos = null;

    public string $optionsText = '';

    public string $mimesText = '';

    public bool $showJson = false;

    public string $jsonText = '';

    public ?string $error = null;

    public ?string $notice = null;

    public array $fieldTypes = [];

    public function mount(Form $form): void
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $this->formId = $form->id;
        $this->title = $form->title;
        $this->description = $form->description ?? '';
        $this->sections = $form->schema['sections'] ?? [];
        $this->published = $form->status === 'published';
        $this->fieldTypes = FieldTypeRegistry::all();
    }

    public function selectField(?string $fieldId): void
    {
        if (! $fieldId) {
            $this->selectedField = null;
            $this->selectedPos = null;

            return;
        }

        foreach ($this->sections as $si => $section) {
            foreach ($section['fields'] as $fi => $field) {
                if ($field['id'] === $fieldId) {
                    $this->selectedPos = [$si, $fi];
                    $this->selectedField = $field;
                    $this->optionsText = collect($field['options'] ?? [])->map(fn ($o) => $o['label'].($o['value'] !== $o['label'] ? ' | '.$o['value'] : ''))->implode("\n");
                    $this->mimesText = implode(',', $field['validation']['mimes'] ?? []);

                    return;
                }
            }
        }

        $this->selectedField = null;
        $this->selectedPos = null;
    }

    public function updatedSelectedField(): void
    {
        if ($this->selectedPos === null || $this->selectedField === null) {
            return;
        }

        [$si, $fi] = $this->selectedPos;
        $this->sections[$si]['fields'][$fi] = $this->selectedField;
    }

    public function updatedOptionsText(): void
    {
        if ($this->selectedField === null) {
            return;
        }

        $options = [];
        foreach (preg_split('/\r\n|\r|\n/', $this->optionsText) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $label = $parts[0];
            $value = $parts[1] ?? $parts[0];
            $options[] = ['label' => $label, 'value' => $value];
        }

        $this->selectedField['options'] = $options;
        $this->updatedSelectedField();
    }

    public function updatedMimesText(): void
    {
        if ($this->selectedField === null) {
            return;
        }

        $this->selectedField['validation']['mimes'] = array_values(array_filter(array_map('trim', explode(',', $this->mimesText))));
        $this->updatedSelectedField();
    }

    public function addField(string $type, ?int $sectionIndex = null): void
    {
        $index = $sectionIndex ?? count($this->sections) - 1;
        if ($index < 0 || ! isset($this->sections[$index])) {
            return;
        }

        $field = FieldTypeRegistry::newField($type);
        $this->sections[$index]['fields'][] = $field;
        $this->selectField($field['id']);
    }

    public function addSection(): void
    {
        $this->sections[] = FieldTypeRegistry::newSection();
    }

    public function addCondition(): void
    {
        if ($this->selectedField === null) {
            return;
        }

        $this->selectedField['conditions'][] = ['field' => '', 'operator' => 'equals', 'value' => ''];
        $this->updatedSelectedField();
    }

    public function removeCondition(int $index): void
    {
        if ($this->selectedField === null) {
            return;
        }

        unset($this->selectedField['conditions'][$index]);
        $this->selectedField['conditions'] = array_values($this->selectedField['conditions']);
        $this->updatedSelectedField();
    }

    public function conditionTargets(): array
    {
        $targets = [];

        foreach ($this->sections as $section) {
            foreach ($section['fields'] as $field) {
                if (($this->selectedField['id'] ?? null) !== $field['id']
                    && FieldTypeRegistry::isInput($field['type'])
                    && $field['type'] !== 'file') {
                    $targets[$field['key']] = $field['label'];
                }
            }
        }

        return $targets;
    }

    public function deleteSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
        $this->selectedField = null;
        $this->selectedPos = null;
    }

    public function duplicateField(int $sectionIndex, int $fieldIndex): void
    {
        $field = $this->sections[$sectionIndex]['fields'][$fieldIndex];
        $copy = $field;
        $copy['id'] = 'fld_'.str()->random(8);
        $copy['key'] = $field['key'].'_copy';

        array_splice($this->sections[$sectionIndex]['fields'], $fieldIndex + 1, 0, [$copy]);
        $this->selectField($copy['id']);
    }

    public function deleteField(int $sectionIndex, int $fieldIndex): void
    {
        unset($this->sections[$sectionIndex]['fields'][$fieldIndex]);
        $this->sections[$sectionIndex]['fields'] = array_values($this->sections[$sectionIndex]['fields']);
        $this->selectedField = null;
        $this->selectedPos = null;
    }

    public function reorderFields(int $sectionIndex, int $from, int $to): void
    {
        if (! isset($this->sections[$sectionIndex]['fields'][$from])) {
            return;
        }

        $fields = $this->sections[$sectionIndex]['fields'];
        $moved = array_splice($fields, $from, 1);
        array_splice($fields, $to, 0, $moved);
        $this->sections[$sectionIndex]['fields'] = array_values($fields);
    }

    public function reorderSections(int $from, int $to): void
    {
        if (! isset($this->sections[$from])) {
            return;
        }

        $moved = array_splice($this->sections, $from, 1);
        array_splice($this->sections, $to, 0, $moved);
        $this->sections = array_values($this->sections);
    }

    public function toggleJson(): void
    {
        $this->showJson = ! $this->showJson;

        if ($this->showJson) {
            $this->jsonText = json_encode($this->schemaArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function applyJson(): void
    {
        $decoded = json_decode($this->jsonText, true);

        if (! is_array($decoded)) {
            $this->error = 'Invalid JSON: '.json_last_error_msg();

            return;
        }

        $validator = app(FormSchemaValidator::class);
        $normalized = $validator->normalize($decoded);
        $result = $validator->validate($normalized);

        if (! $result['valid']) {
            $this->error = 'Schema is invalid: '.implode(' ', array_slice($result['errors'], 0, 5));

            return;
        }

        $this->title = $normalized['title'] ?: $this->title;
        $this->description = $normalized['description'] ?? '';
        $this->sections = $normalized['sections'];
        $this->selectedField = null;
        $this->selectedPos = null;
        $this->showJson = false;
        $this->error = null;
        $this->notice = 'Schema applied to the canvas.';
    }

    public function save(): void
    {
        $validator = app(FormSchemaValidator::class);
        $normalized = $validator->normalize($this->schemaArray());
        $result = $validator->validate($normalized);

        if (! $result['valid']) {
            $this->error = 'Cannot save: '.implode(' ', array_slice($result['errors'], 0, 5));

            return;
        }

        $form = Form::findOrFail($this->formId);
        $form->title = $this->title;
        $form->description = $this->description;
        $form->schema = $normalized;
        $form->status = $this->published ? 'published' : 'draft';
        $form->schema_version++;
        $form->save();

        $form->versions()->create([
            'version' => $form->schema_version,
            'schema' => $normalized,
            'note' => 'Saved from builder',
            'created_by' => auth()->id(),
        ]);

        $this->error = null;
        $this->notice = 'Form saved. Schema version v'.$form->schema_version.' recorded.';

        if ($this->published) {
            $this->notice .= ' Public URL: '.$form->publicUrl();
        }
    }

    protected function schemaArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'sections' => $this->sections,
        ];
    }

    public function render()
    {
        return view('livewire.forms.builder', [
            'form' => Form::find($this->formId),
        ])->layout('layouts.app');
    }
}
