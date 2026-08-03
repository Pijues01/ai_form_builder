<?php

namespace App\Services\Import;

use App\Services\AI\AiClient;
use App\Services\AI\JsonExtractor;
use App\Services\Schema\FieldTypeRegistry;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orchestrates document -> editable form draft.
 *
 * Parsing is deterministic first (DocxParser / XlsxParser + FieldTypeGuesser).
 * Only when a field is genuinely ambiguous (low confidence) AND an
 * OpenAI-compatible driver is configured does it call the LLM to infer the
 * type. The AI is a suggestion layer: every result is still validated against
 * the field-type whitelist and falls back to the heuristic guess.
 */
class FormImportService
{
    public function __construct(
        private readonly DocxParser $docx,
        private readonly XlsxParser $xlsx,
        private readonly AiClient $ai,
    ) {}

    /**
     * @return array{title: string, description: string, sections: array, warnings: array<int, string>}
     */
    public function parse(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        $draft = match ($extension) {
            'docx' => $this->docx->parse($path),
            'xlsx' => $this->xlsx->parse($path),
            default => throw new RuntimeException("Unsupported file type .{$extension}. Upload a .docx or .xlsx file."),
        };

        if (config('ai.driver') === 'openai') {
            $draft = $this->aiAssist($draft);
        }

        return $draft;
    }

    /**
     * Optional AI pass over ambiguous fields. Never throws; failures keep the
     * deterministic guess so the import always proceeds.
     */
    private function aiAssist(array $draft): array
    {
        $ambiguous = [];

        foreach ($draft['sections'] as $si => $section) {
            foreach ($section['fields'] as $fi => $field) {
                if (($field['confidence'] ?? 'low') === 'low' && $field['type'] === 'text') {
                    $ambiguous[] = ['si' => $si, 'fi' => $fi, 'label' => $field['label']];
                }
            }
        }

        if ($ambiguous === []) {
            return $draft;
        }

        $labels = implode('" / "', array_map(fn ($a) => $a['label'], $ambiguous));

        try {
            $response = $this->ai->chat([
                ['role' => 'system', 'content' => 'You map user-visible question text to form field types. Output ONLY JSON.'],
                ['role' => 'user', 'content' => sprintf(
                    'For each question choose exactly one type from: %s. Return a JSON object keyed by the exact question text, e.g. {"%s": "text"}.',
                    implode(', ', array_keys(FieldTypeRegistry::inputTypes())),
                    $ambiguous[0]['label']
                )],
                ['role' => 'user', 'content' => 'Questions: "'.$labels.'"'],
            ]);

            $map = JsonExtractor::extract($response['content']);

            if (! is_array($map)) {
                return $draft;
            }

            foreach ($ambiguous as $entry) {
                $type = is_string($map[$entry['label']] ?? null) ? strtolower($map[$entry['label']]) : null;

                if ($type !== null && FieldTypeRegistry::exists($type)) {
                    $draft['sections'][$entry['si']]['fields'][$entry['fi']]['type'] = $type;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI import assist skipped', ['error' => $e->getMessage()]);
        }

        return $draft;
    }
}
