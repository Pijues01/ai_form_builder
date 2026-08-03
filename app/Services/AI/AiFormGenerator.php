<?php

namespace App\Services\AI;

use App\Services\Schema\FieldTypeRegistry;
use App\Services\Schema\FormSchemaValidator;
use RuntimeException;

class AiFormGenerator
{
    public const FIELD_WHITELIST = [
        'text', 'textarea', 'number', 'email', 'phone', 'url', 'date', 'time',
        'dropdown', 'radio', 'checkbox', 'file', 'rating',
    ];

    public function __construct(
        private readonly AiClient $client,
        private readonly FormSchemaValidator $validator,
    ) {}

    /**
     * Generate (or modify) a form schema from a natural language prompt.
     *
     * @param  array{title: string, sections: array}  $existingSchema  for edit mode
     * @return array{schema: array, model: string|null, tokens: int|null, latency_ms: int, attempts: int}
     */
    public function generate(string $prompt, ?array $existingSchema = null): array
    {
        $messages = $this->messages($prompt, $existingSchema);
        $attempts = 0;
        $maxAttempts = 1 + (int) config('ai.retries');

        while ($attempts < $maxAttempts) {
            $attempts++;

            $response = $this->client->chat($messages);

            $json = JsonExtractor::extract($response['content']);
            if ($json === null) {
                $messages[] = ['role' => 'assistant', 'content' => $response['content']];
                $messages[] = ['role' => 'user', 'content' => 'Your last response did not contain valid JSON. Respond with ONLY a valid JSON object, no markdown or prose.'];
                continue;
            }

            $schema = $json;

            $check = $this->validator->validate($schema);
            if (! $check['valid']) {
                $schema = $this->validator->normalize($schema);
                $check = $this->validator->validate($schema);
            }

            if ($check['valid']) {
                return [
                    'schema' => $this->validator->normalize($schema),
                    'model' => $response['model'],
                    'tokens' => $response['total_tokens'],
                    'latency_ms' => $response['latency_ms'],
                    'attempts' => $attempts,
                ];
            }

            $messages[] = ['role' => 'assistant', 'content' => $response['content']];
            $messages[] = ['role' => 'user', 'content' => 'Your schema failed validation. Fix these errors and return ONLY valid JSON: '.implode(' | ', $check['errors'])];
        }

        throw new RuntimeException('AI could not produce a valid form schema after '.$maxAttempts.' attempts.');
    }

    /**
     * @param  array{title: string, sections: array}|null  $existingSchema
     * @return array<int, array{role: string, content: string}>
     */
    protected function messages(string $prompt, ?array $existingSchema = null): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt()],
        ];

        if ($existingSchema === null) {
            $messages[] = ['role' => 'user', 'content' => "Create a brand new form.\n\nRequest: {$prompt}"];
        } else {
            $messages[] = ['role' => 'user', 'content' => "Modify the existing form below based on this request: {$prompt}\n\nKeep the structure and fields unless the request says otherwise. Return the COMPLETE updated schema.\n\nCurrent schema:\n".json_encode($existingSchema, JSON_PRETTY_PRINT)];
        }

        return $messages;
    }

    protected function systemPrompt(): string
    {
        $types = implode(', ', self::FIELD_WHITELIST);

        return <<<PROMPT
You are an expert form designer that outputs only JSON.

Given a request, design a clean, production-ready form schema.

Allowed field types (use ONLY these): {$types}

Return a single JSON object with this exact shape:

{
  "title": "Form title",
  "description": "Optional one-line description",
  "sections": [
    {
      "title": "Section title",
      "fields": [
        {
          "type": "text",
          "label": "Field label",
          "key": "unique_snake_case_key",
          "placeholder": "optional placeholder",
          "help_text": null,
          "default": null,
          "required": false,
          "options": [],
          "validation": {
            "min": null, "max": null, "min_length": null, "max_length": null,
            "step": null, "pattern": null, "min_selections": null, "max_selections": null,
            "mimes": [], "max_size": null, "max_files": null
          }
        }
      ]
    }
  ]
}

Rules:
- Use 1-3 sections grouped by topic; each section has 3-8 fields.
- "options" is required for dropdown, radio and checkbox fields (array of strings, 2-6 entries).
- "file" fields should set validation.mimes (e.g. ["pdf","doc","docx"]) and validation.max_size in KB.
- Number/date fields should set sensible min/max when appropriate.
- Only include validation entries that are meaningful; keep others null.
- For dropdown/radio/checkbox use realistic values (e.g. ["Full-time", "Part-time"]).
- Do NOT include keys, ids or fields outside this contract. No markdown, no prose, no code fences - ONLY the JSON object.
PROMPT;
    }
}
