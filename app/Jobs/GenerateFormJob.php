<?php

namespace App\Jobs;

use App\Models\AiGeneration;
use App\Services\AI\FormGenerator;
use App\Services\Schema\FormSchemaValidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFormJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public AiGeneration $generation,
    ) {}

    /**
     * Run the (possibly long) LLM call off the web request and record
     * model, token usage and latency against the generation row.
     */
    public function handle(FormGenerator $generator, FormSchemaValidator $validator): void
    {
        $this->generation->update(['status' => 'processing']);

        $provider = config('ai.driver');

        try {
            $result = $generator->generate($this->generation->prompt, $this->generation->input);

            $schema = $validator->normalize($result['schema']);
            $check = $validator->validate($schema);

            if (! $check['valid']) {
                throw new \RuntimeException('Generated schema failed validation: '.implode(' | ', $check['errors']));
            }

            $meta = [
                'schema' => $schema,
                'model' => $result['model'] ?? null,
                'tokens' => $result['tokens'] ?? null,
                'latency_ms' => $result['latency_ms'] ?? null,
                'attempts' => $result['attempts'] ?? null,
            ];

            $this->generation->update([
                'status' => 'completed',
                'provider' => $provider,
                'model' => $meta['model'],
                'tokens_total' => $meta['tokens'],
                'latency_ms' => $meta['latency_ms'],
                'repair_attempts' => max(0, (int) $meta['attempts'] - 1),
                'result' => $meta,
                'error' => null,
            ]);

            Log::info('AI generation completed', [
                'generation' => $this->generation->id,
                'provider' => $provider,
                'model' => $meta['model'],
                'tokens' => $meta['tokens'],
                'latency_ms' => $meta['latency_ms'],
            ]);
        } catch (\Throwable $e) {
            Log::error('AI generation failed', [
                'generation' => $this->generation->id,
                'error' => $e->getMessage(),
            ]);

            $this->generation->update([
                'status' => 'failed',
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
