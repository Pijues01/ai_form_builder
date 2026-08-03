<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Services\Schema\FieldTypeRegistry;
use Livewire\Component;

class FormList extends Component
{
    public string $search = '';

    public function create(): void
    {
        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => 'Untitled form',
            'slug' => Form::uniqueSlug('Untitled form'),
            'schema' => ['title' => 'Untitled form', 'description' => '', 'sections' => [
                ['id' => 'sec_'.str()->random(8), 'title' => 'Untitled section', 'fields' => []],
            ]],
            'settings' => ['confirmation_message' => 'Thanks! Your response has been recorded.'],
        ]);

        $this->redirectRoute('forms.edit', $form);
    }

    public function delete(int $formId): void
    {
        $form = Form::where('user_id', auth()->id())->findOrFail($formId);
        $form->delete();

        session()->flash('status', 'Form deleted.');
    }

    public function render()
    {
        return view('livewire.forms.list', [
            'forms' => Form::query()
                ->where('user_id', auth()->id())
                ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->withCount('submissions')
                ->orderByDesc('updated_at')
                ->paginate(12),
            'fieldTypes' => FieldTypeRegistry::all(),
        ])->layout('layouts.app');
    }
}
