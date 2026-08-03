<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\Schema\FieldTypeRegistry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionExportController extends Controller
{
    public function csv(Form $form, Request $request): StreamedResponse
    {
        abort_unless($form->user_id === auth()->id(), 403);

        $schema = $form->schema ?? ['sections' => []];
        $fields = collect();

        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $fields->push($field);
            }
        }

        $inputFields = $fields->filter(fn ($f) => FieldTypeRegistry::isInput($f['type']))->values();

        $filename = 'submissions-'.str()->slug($form->title).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($form, $inputFields, $request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_merge(['Submitted At'], $inputFields->pluck('key')->all(), ['IP']));

            $form->submissions()
                ->when($request->query('search'), fn ($q) => $q->where('searchable', 'like', '%'.$request->query('search').'%'))
                ->orderByDesc('created_at')
                ->chunk(500, function ($rows) use ($handle, $inputFields) {
                    foreach ($rows as $row) {
                        $data = $row->data ?? [];
                        $line = [$row->created_at->format('Y-m-d H:i:s')];

                        foreach ($inputFields as $field) {
                            $value = $data[$field['key']] ?? '';
                            $line[] = is_array($value) ? implode('; ', $value) : (string) $value;
                        }

                        $line[] = $row->ip;
                        fputcsv($handle, $line);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
