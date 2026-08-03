<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use Livewire\Component;

class FormVersions extends Component
{
    public ?int $formId = null;

    public function mount(Form $form): void
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $this->formId = $form->id;
    }

    public function restore(int $versionId): void
    {
        $form = Form::findOrFail($this->formId);
        $version = $form->versions()->findOrFail($versionId);

        $form->schema = $version->schema;
        $form->title = $version->schema['title'] ?? $form->title;
        $form->description = $version->schema['description'] ?? $form->description;
        $form->schema_version++;
        $form->save();

        $form->versions()->create([
            'version' => $form->schema_version,
            'schema' => $form->schema,
            'note' => 'Restored from v'.$version->version,
            'created_by' => auth()->id(),
        ]);

        session()->flash('status', 'Form restored from v'.$version->version.'.');
    }

    public function render()
    {
        return view('livewire.forms.versions', [
            'form' => Form::findOrFail($this->formId),
        ])->layout('layouts.app');
    }
}
