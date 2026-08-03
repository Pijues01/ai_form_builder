<?php

namespace App\Livewire\Forms;

use App\Models\Form;
use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Support\Collection;
use Livewire\Component;

class FormAnalytics extends Component
{
    public ?int $formId = null;

    public int $days = 14;

    public function mount(Form $form): void
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $this->formId = $form->id;
    }

    public function updatedDays(): void
    {
        $this->days = max(1, min(90, (int) $this->days));
    }

    public function render()
    {
        $form = Form::findOrFail($this->formId);
        $schema = app(FormSchemaValidator::class)->normalize($form->schema ?? ['sections' => []]);

        $stats = $this->stats($form, $schema);

        return view('livewire.forms.analytics', [
            'form' => $form,
            'stats' => $stats,
        ])->layout('layouts.app');
    }

    protected function stats(Form $form, array $schema): array
    {
        $fields = array_values(array_filter(
            app(FormSchemaValidator::class)->allFields($schema),
            fn ($field) => FieldTypeRegistry::isInput($field['type'])
        ));

        $total = $form->submissions()->count();

        $daily = $form->submissions()
            ->where('created_at', '>=', now()->subDays($this->days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->mapWithKeys(fn ($count, $day) => [(string) $day => (int) $count]);

        $rows = $form->submissions()->get(['data', 'metadata']);

        $perField = $this->fieldCompletion($rows, $fields, $total);

        $fillingTimes = $rows
            ->map(fn ($row) => (int) ($row->metadata['filling_time_seconds'] ?? 0))
            ->filter(fn ($seconds) => $seconds > 0);

        $answered = $rows->map(function ($row) use ($fields) {
            $value = $row->data ?? [];

            return count(array_filter($fields, fn ($field) => $this->isAnswered($value, $field['key'])));
        });

        return [
            'total' => $total,
            'daily' => $daily,
            'fields' => $fields,
            'perField' => $perField,
            'averageFillingSeconds' => $fillingTimes->isNotEmpty() ? (int) round($fillingTimes->avg()) : 0,
            'averageAnswered' => $rows->isNotEmpty() && $fields ? (float) round($answered->avg(), 1) : 0.0,
            'fieldCount' => count($fields),
            'maxDaily' => $daily->max() ?: 0,
        ];
    }

    protected function fieldCompletion(Collection $rows, array $fields, int $total): array
    {
        $result = [];

        foreach ($fields as $field) {
            $answered = $rows->filter(fn ($row) => $this->isAnswered($row->data ?? [], $field['key']))->count();
            $result[] = [
                'label' => $field['label'],
                'key' => $field['key'],
                'type' => $field['type'],
                'answered' => $answered,
                'rate' => $total > 0 ? round(($answered / $total) * 100, 1) : 0.0,
            ];
        }

        usort($result, fn ($a, $b) => $a['rate'] <=> $b['rate']);

        return $result;
    }

    protected function isAnswered(array $data, string $key): bool
    {
        if (! array_key_exists($key, $data)) {
            return false;
        }

        $value = $data[$key];

        if (is_array($value)) {
            return count(array_filter($value, fn ($v) => $v !== '' && $v !== null)) > 0;
        }

        return $value !== '' && $value !== null;
    }
}
