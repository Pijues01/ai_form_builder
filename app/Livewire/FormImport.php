<?php

namespace App\Livewire;

use App\Jobs\ImportFileJob;
use App\Models\Form;
use App\Models\ImportPreview;
use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class FormImport extends Component
{
    use WithFileUploads;

    public $importFile;

    public ?int $previewId = null;

    public array $draft = [];

    public ?string $error = null;

    public array $fieldTypes = [];

    public function mount(?int $preview = null): void
    {
        $this->previewId = $preview;
        $this->fieldTypes = FieldTypeRegistry::all();

        $current = $this->currentPreview();
        if ($current !== null && $current->status === 'completed') {
            $this->draft = $current->result ?? [];
        }
    }

    public function upload(): void
    {
        $this->error = null;

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:docx,xlsx', 'max:10240'],
        ], [
            'importFile.required' => 'Choose a .docx or .xlsx file to import.',
            'importFile.mimes' => 'Only .docx and .xlsx files are supported.',
        ]);

        $original = $this->importFile->getClientOriginalName();
        $extension = strtolower($this->importFile->getClientOriginalExtension());

        $path = $this->importFile->storeAs(
            'imports/'.auth()->id(),
            Str::uuid().'.'.$extension,
            'local'
        );

        $preview = ImportPreview::create([
            'user_id' => auth()->id(),
            'original_filename' => $original,
            'file_type' => $extension,
            'disk' => 'local',
            'file_path' => $path,
            'status' => 'queued',
        ]);

        ImportFileJob::dispatch($preview);

        $this->redirectRoute('import.show', ['preview' => $preview->id]);
    }

    public function currentPreview(): ?ImportPreview
    {
        if ($this->previewId === null) {
            return null;
        }

        $preview = ImportPreview::find($this->previewId);

        return $preview && $preview->user_id === auth()->id() ? $preview : null;
    }

    /**
     * Split a comma/pipe separated options string into an option array.
     */
    public function setOptions(int $sectionIndex, int $fieldIndex, string $value): void
    {
        $options = array_values(array_filter(array_map('trim', preg_split('/[|,;]/', $value) ?: []), fn ($o) => $o !== ''));

        if (isset($this->draft['sections'][$sectionIndex]['fields'][$fieldIndex])) {
            $this->draft['sections'][$sectionIndex]['fields'][$fieldIndex]['options'] = array_slice($options, 0, 12);
        }
    }

    public function createForm(): void
    {
        $this->error = null;

        $preview = $this->currentPreview();
        if ($preview === null || $preview->status !== 'completed') {
            $this->error = 'Parsing is not finished yet.';

            return;
        }

        $validator = app(FormSchemaValidator::class);
        $schema = $this->buildSchema();
        $schema = $validator->normalize($schema);
        $check = $validator->validate($schema);

        if (! $check['valid']) {
            $this->error = 'Cannot create form: '.implode(' | ', array_slice($check['errors'], 0, 3));

            return;
        }

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $schema['title'] ?: 'Imported form',
            'slug' => Form::uniqueSlug($schema['title'] ?: 'Imported form'),
            'description' => $schema['description'] ?? null,
            'schema' => $schema,
            'schema_version' => 1,
            'status' => 'draft',
            'settings' => ['confirmation_message' => 'Thanks! Your response has been recorded.'],
        ]);

        $form->versions()->create([
            'version' => 1,
            'schema' => $schema,
            'note' => 'Imported from '.$preview->original_filename,
            'created_by' => auth()->id(),
        ]);

        $preview->update(['form_id' => $form->id]);

        $this->redirectRoute('forms.edit', ['form' => $form->id]);
    }

    protected function buildSchema(): array
    {
        $sections = [];

        foreach ($this->draft['sections'] ?? [] as $section) {
            $fields = [];

            foreach ($section['fields'] ?? [] as $field) {
                $fields[] = [
                    'type' => $field['type'] ?? 'text',
                    'label' => $field['label'] ?? '',
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => is_array($field['options'] ?? null) ? array_values($field['options']) : [],
                    'help_text' => $field['help_text'] ?? null,
                ];
            }

            if ($fields !== []) {
                $sections[] = [
                    'title' => $section['title'] ?? 'Imported Questions',
                    'fields' => $fields,
                ];
            }
        }

        return [
            'title' => $this->draft['title'] ?? 'Imported form',
            'description' => $this->draft['description'] ?? 'Imported from a document.',
            'sections' => $sections,
        ];
    }

    public function render()
    {
        $preview = $this->currentPreview();

        if ($preview !== null && $preview->status === 'completed' && $this->draft === []) {
            $this->draft = $preview->result ?? [];
        }

        return view('livewire.form-import', [
            'preview' => $preview,
            'history' => ImportPreview::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->take(10)
                ->get(),
            'polling' => in_array($preview?->status, ['queued', 'processing'], true),
        ])->layout('layouts.app');
    }
}
