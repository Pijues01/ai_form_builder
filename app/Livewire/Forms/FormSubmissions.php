<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use Livewire\Component;
use Livewire\WithPagination;

class FormSubmissions extends Component
{
    use WithPagination;

    public ?int $formId = null;

    public string $search = '';

    public function mount(Form $form): void
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $this->formId = $form->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $form = Form::findOrFail($this->formId);

        return view('livewire.forms.submissions', [
            'form' => $form,
            'submissions' => $form->submissions()
                ->when($this->search, fn ($q) => $q->where('searchable', 'like', '%'.$this->search.'%'))
                ->orderByDesc('created_at')
                ->paginate(20),
        ])->layout('layouts.app');
    }
}
