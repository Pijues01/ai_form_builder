<?php

namespace App\Livewire;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class PublicForm extends Component
{
    use WithFileUploads;

    public ?int $formId = null;

    public array $values = [];

    public bool $submitted = false;

    public string $honeypot = '';

    public int $startedAt = 0;

    public function mount(Form $form): void
    {
        abort_unless($form->status === 'published', 404);

        $this->formId = $form->id;
        $this->startedAt = time();

        foreach ($this->schema($form)['sections'] ?? [] as $section) {
            foreach ($section['fields'] as $field) {
                if (FieldTypeRegistry::isInput($field['type']) && $field['default'] !== null && $field['default'] !== '') {
                    $this->values[$field['key']] = $field['default'];
                }
            }
        }
    }

    protected function schema(?Form $form = null): array
    {
        $form = $form ?? Form::findOrFail($this->formId);

        return app(FormSchemaValidator::class)->normalize($form->schema ?? ['sections' => []]);
    }

    public function updatedValues(): void
    {
        // re-evaluate conditional fields for live visibility
    }

    public function setValue(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function submit(): void
    {
        $form = Form::findOrFail($this->formId);
        $validator = app(FormSchemaValidator::class);
        $schema = $this->schema($form);

        if ($this->honeypot !== '') {
            $this->values = [];

            return;
        }

        if (time() - $this->startedAt < 3) {
            $this->values = [];

            return;
        }

        $rules = $validator->rules($schema, $this->values);

        $prefixed = [];
        foreach ($rules as $key => $fieldRules) {
            $prefixed['values.'.$key] = $fieldRules;
        }

        $messages = [
            'required' => 'This field is required.',
            'email' => 'Please enter a valid email address.',
            'url' => 'Please enter a valid URL.',
            'date' => 'Please enter a valid date.',
            'numeric' => 'Please enter a number.',
            'mimes' => 'The file type is not allowed.',
            'max' => 'The value or file is too large.',
        ];

        $this->validate($prefixed, $messages);

        $stored = [];
        foreach ($this->values as $key => $value) {
            if ($value instanceof TemporaryUploadedFile) {
                $stored[$key] = $value->store('uploads', 'public');
            } elseif (is_array($value)) {
                $stored[$key] = array_values($value);
            } else {
                $stored[$key] = $value;
            }
        }

        $searchable = implode(' ', array_map(
            fn ($v) => is_array($v) ? implode(' ', $v) : (string) $v,
            $stored
        ));

        FormSubmission::create([
            'form_id' => $form->id,
            'data' => $stored,
            'searchable' => $searchable,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'filling_time_seconds' => max(0, time() - $this->startedAt),
            ],
        ]);

        $this->values = [];
        $this->submitted = true;
    }

    public function render()
    {
        $form = Form::findOrFail($this->formId);
        $validator = app(FormSchemaValidator::class);
        $schema = $this->schema($form);

        $visibleSections = [];
        foreach ($schema['sections'] as $section) {
            $visibleFields = array_values(array_filter(
                $section['fields'],
                fn ($field) => $validator->isVisible($field, $this->values)
            ));

            if (! empty($visibleFields)) {
                $visibleSections[] = [
                    'id' => $section['id'] ?? null,
                    'title' => $section['title'] ?? null,
                    'fields' => $visibleFields,
                ];
            }
        }

        return view('livewire.public-form', [
            'form' => $form,
            'visibleSections' => $visibleSections,
            'settings' => $form->settings ?? [],
        ])->layout('layouts.public');
    }
}
