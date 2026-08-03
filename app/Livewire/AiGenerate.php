<?php

namespace App\Livewire;

use App\Jobs\GenerateFormJob;
use App\Models\AiGeneration;
use App\Models\Form;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Support\Str;
use Livewire\Component;

class AiGenerate extends Component
{
    public string $prompt = '';

    public string $mode = 'create';

    public ?int $editFormId = null;

    public ?int $generationId = null;

    public ?string $error = null;

    public ?string $notice = null;

    public function mount(?int $generation = null): void
    {
        $this->generationId = $generation;
    }

    public function generate(): void
    {
        $this->error = null;

        $this->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'mode' => ['required', 'in:create,edit'],
            'editFormId' => ['required_if:mode,edit', 'nullable', 'integer'],
        ], [
            'prompt.required' => 'Describe the form you want to build.',
            'editFormId.required_if' => 'Pick the form you want the AI to edit.',
        ]);

        $input = null;
        $formId = null;

        if ($this->mode === 'edit') {
            $form = Form::where('user_id', auth()->id())->findOrFail($this->editFormId);
            $input = $form->schema;
            $formId = $form->id;
        }

        $generation = AiGeneration::create([
            'user_id' => auth()->id(),
            'form_id' => $formId,
            'mode' => $this->mode,
            'prompt' => $this->prompt,
            'input' => $input,
            'status' => 'queued',
        ]);

        GenerateFormJob::dispatch($generation);

        $this->redirectRoute('ai.show', ['generation' => $generation->id]);
    }

    /**
     * Apply a completed generation to a form and open it in the builder.
     */
    public function apply(): void
    {
        $this->error = null;

        $generation = $this->currentGeneration();
        if ($generation === null || $generation->status !== 'completed') {
            $this->error = 'Generation is not ready yet.';

            return;
        }

        $schema = $generation->result['schema'] ?? null;
        if (! is_array($schema)) {
            $this->error = 'No usable schema in this generation.';

            return;
        }

        $validator = app(FormSchemaValidator::class);
        $schema = $validator->normalize($schema);
        $check = $validator->validate($schema);
        if (! $check['valid']) {
            $this->error = 'Schema invalid: '.implode(' | ', array_slice($check['errors'], 0, 3));

            return;
        }

        $form = $this->persist($generation, $schema, $validator);

        $this->notice = null;
        $this->redirectRoute('forms.edit', ['form' => $form->id]);
    }

    protected function persist(AiGeneration $generation, array $schema, FormSchemaValidator $validator): Form
    {
        if ($generation->mode === 'create') {
            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => $schema['title'] ?: 'AI generated form',
                'slug' => Form::uniqueSlug($schema['title'] ?: 'AI generated form'),
                'description' => $schema['description'] ?? null,
                'schema' => $schema,
                'schema_version' => 1,
                'status' => 'draft',
                'settings' => ['confirmation_message' => 'Thanks! Your response has been recorded.'],
            ]);

            $generation->update(['form_id' => $form->id]);

            return $form;
        }

        $form = Form::where('user_id', auth()->id())->findOrFail($generation->form_id);
        $form->title = $schema['title'] ?: $form->title;
        $form->description = $schema['description'] ?? $form->description;
        $form->schema = $schema;
        $form->schema_version++;
        $form->save();

        $form->versions()->create([
            'version' => $form->schema_version,
            'schema' => $schema,
            'note' => 'AI edit: '.Str::limit($generation->prompt, 80),
            'created_by' => auth()->id(),
        ]);

        return $form;
    }

    public function retry(): void
    {
        $generation = $this->currentGeneration();
        if ($generation === null) {
            return;
        }

        $generation->update(['status' => 'queued', 'error' => null]);
        GenerateFormJob::dispatch($generation->fresh());
    }

    public function currentGeneration(): ?AiGeneration
    {
        if ($this->generationId === null) {
            return null;
        }

        $generation = AiGeneration::find($this->generationId);

        return $generation && $generation->user_id === auth()->id() ? $generation : null;
    }

    public function render()
    {
        return view('livewire.ai-generate', [
            'generation' => $this->currentGeneration(),
            'history' => AiGeneration::query()
                ->where('user_id', auth()->id())
                ->latest()
                ->take(10)
                ->get(),
            'forms' => Form::query()
                ->where('user_id', auth()->id())
                ->orderBy('title')
                ->get(['id', 'title', 'schema_version']),
            'polling' => in_array($this->currentGeneration()?->status, ['queued', 'processing'], true),
        ])->layout('layouts.app');
    }
}
