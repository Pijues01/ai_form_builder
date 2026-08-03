<?php

namespace App\Livewire;

use App\Models\Form;
use App\Services\Schema\FormSchemaValidator;
use App\Services\Templates\FormTemplates;
use Livewire\Component;

class Templates extends Component
{
    public function useTemplate(string $slug): void
    {
        $template = FormTemplates::get($slug);

        abort_unless($template !== null, 404);

        $schema = app(FormSchemaValidator::class)->normalize($template['schema']);

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $schema['title'],
            'slug' => Form::uniqueSlug($schema['title']),
            'description' => $schema['description'],
            'schema' => $schema,
            'schema_version' => 1,
            'status' => 'draft',
            'settings' => ['confirmation_message' => 'Thanks! Your response has been recorded.'],
        ]);

        $form->versions()->create([
            'version' => 1,
            'schema' => $schema,
            'note' => 'Created from template "'.$template['name'].'"',
            'created_by' => auth()->id(),
        ]);

        session()->flash('status', 'Form created from the "'.$template['name'].'" template.');

        $this->redirectRoute('forms.edit', $form);
    }

    public function render()
    {
        return view('livewire.templates', [
            'templates' => FormTemplates::all(),
        ])->layout('layouts.app');
    }
}
