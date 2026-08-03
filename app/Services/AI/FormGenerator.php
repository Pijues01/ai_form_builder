<?php

namespace App\Services\AI;

use App\Services\Schema\FormSchemaValidator;

class FormGenerator
{
    public function __construct(
        private readonly AiClient $client,
        private readonly MockFormGenerator $mock,
        private readonly FormSchemaValidator $validator,
    ) {}

    /**
     * @param  array{title: string, sections: array}|null  $existingSchema
     * @return array{schema: array, model: string|null, tokens: int|null, latency_ms: int, attempts: int}
     */
    public function generate(string $prompt, ?array $existingSchema = null): array
    {
        if (config('ai.driver') === 'mock') {
            return $this->mock->generate($prompt, $existingSchema);
        }

        return (new AiFormGenerator($this->client, $this->validator))->generate($prompt, $existingSchema);
    }
}
