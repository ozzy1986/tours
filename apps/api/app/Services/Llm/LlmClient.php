<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Models\LlmSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

class LlmClient
{
    /**
     * Send a chat completion. When $expectsJson is true, asks for JSON object output
     * and returns parsed array; otherwise returns plain text content.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @return string|array<string, mixed>
     */
    public function chat(array $messages, bool $expectsJson = false, ?float $temperature = null, ?int $maxTokens = null): string|array
    {
        $cfg = $this->resolveConfig();

        if (! $cfg['enabled']) {
            throw new LlmException('LLM integration is disabled. Open Filament > Настройки LLM.');
        }

        // For deterministic structured output keep temperature low regardless of UI setting.
        $temp = $temperature ?? ($expectsJson ? min((float) $cfg['temperature'], 0.2) : (float) $cfg['temperature']);

        $payload = [
            'model' => $cfg['model'],
            'temperature' => $temp,
            'max_tokens' => $maxTokens ?? $cfg['max_tokens'],
            'messages' => $messages,
            'stream' => false,
        ];

        if ($expectsJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        if ($cfg['provider'] === 'ollama') {
            // Keep the model warm between calls to avoid 100+ s reloads on cold start.
            $payload['keep_alive'] = '15m';
        }

        try {
            $response = $this->httpClient($cfg)
                ->post(rtrim($cfg['base_url'], '/') . '/chat/completions', $payload);
        } catch (Throwable $e) {
            throw new LlmException("LLM request failed: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new LlmException("LLM API HTTP {$response->status()}: {$response->body()}");
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new LlmException('LLM returned empty content');
        }

        if (! $expectsJson) {
            return $content;
        }

        return $this->decodeJson($content);
    }

    /**
     * Quick connectivity check used by the admin "Test connection" button.
     */
    public function ping(): string
    {
        $reply = $this->chat([
            ['role' => 'system', 'content' => 'Reply with the single word: pong.'],
            ['role' => 'user', 'content' => 'ping'],
        ], expectsJson: false, maxTokens: 16);

        return is_string($reply) ? trim($reply) : 'ok';
    }

    /**
     * Build the HTTP client. Skips Authorization for providers that don't need it (Ollama).
     *
     * @param  array{provider:string, base_url:string, api_key:?string, ...}  $cfg
     */
    private function httpClient(array $cfg): PendingRequest
    {
        $client = Http::timeout((int) config('services.llm.timeout', 600))
            ->connectTimeout(15)
            ->acceptJson()
            ->asJson();

        if (filled($cfg['api_key'])) {
            $client = $client->withToken($cfg['api_key']);
        }

        return $client;
    }

    /**
     * Decode LLM JSON response, tolerating markdown code fences and surrounding noise.
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $content): array
    {
        $cleaned = $this->stripJsonNoise($content);

        $parsed = json_decode($cleaned, associative: true);

        if (! is_array($parsed)) {
            throw new LlmException(
                'LLM JSON output could not be parsed. Raw start: ' . substr(trim($content), 0, 200)
            );
        }

        return $parsed;
    }

    private function stripJsonNoise(string $raw): string
    {
        $s = trim($raw);

        // Strip ```json ... ``` or ``` ... ``` wrappers some small models add despite instructions.
        if (str_starts_with($s, '```')) {
            $s = preg_replace('/^```(?:json|JSON)?\s*\n?/', '', $s) ?? $s;
            $s = preg_replace('/\n?```\s*$/', '', $s) ?? $s;
            $s = trim($s);
        }

        if ($s === '' || $s[0] === '{' || $s[0] === '[') {
            return $s;
        }

        // Last-resort fallback: extract first {...} or [...] block from a noisy reply.
        if (preg_match('/(\{.*\}|\[.*\])/s', $s, $m)) {
            return $m[1];
        }

        return $s;
    }

    /**
     * @return array{provider:string, base_url:string, api_key:?string, model:string, temperature:float, max_tokens:int, enabled:bool}
     */
    private function resolveConfig(): array
    {
        $setting = LlmSetting::current();

        if ($setting->isUsable()) {
            return [
                'provider' => $setting->provider,
                'base_url' => $setting->base_url,
                'api_key' => $setting->api_key,
                'model' => $setting->model,
                'temperature' => (float) $setting->temperature,
                'max_tokens' => (int) $setting->max_tokens,
                'enabled' => true,
            ];
        }

        $fallback = config('services.llm');
        $provider = $fallback['provider'];
        $key = $fallback['api_key'];

        return [
            'provider' => $provider,
            'base_url' => $fallback['base_url'],
            'api_key' => $key,
            'model' => $fallback['model'],
            'temperature' => (float) $fallback['temperature'],
            'max_tokens' => (int) $fallback['max_tokens'],
            'enabled' => $provider === 'ollama' || filled($key),
        ];
    }
}
