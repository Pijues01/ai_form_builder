<?php

namespace App\Services\AI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiClient
{
    /**
     * Call a chat-completions style API. Returns normalized response metadata.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, model: string|null, prompt_tokens: int|null, completion_tokens: int|null, total_tokens: int|null, latency_ms: int}
     */
    public function chat(array $messages): array
    {
        $config = config('ai');
        $start = microtime(true);

        $response = Http::withToken($config['api_key'])
            ->timeout($config['timeout'])
            ->retry(2, 2000, throw: false)
            ->post($config['base_url'].'/chat/completions', [
                'model' => $config['model'],
                'messages' => $messages,
                'max_tokens' => $config['max_tokens'],
                'temperature' => $config['temperature'],
                'response_format' => ['type' => 'json_object'],
            ]);

        $latency = (int) round((microtime(true) - $start) * 1000);

        if ($response->failed()) {
            Log::error('AI request failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 1000),
            ]);

            throw new \RuntimeException('AI request failed with HTTP '.$response->status().'.');
        }

        $payload = $response->json();

        return [
            'content' => $payload['choices'][0]['message']['content'] ?? '',
            'model' => $payload['model'] ?? $config['model'],
            'prompt_tokens' => $payload['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $payload['usage']['completion_tokens'] ?? null,
            'total_tokens' => $payload['usage']['total_tokens'] ?? null,
            'latency_ms' => $latency,
        ];
    }
}
