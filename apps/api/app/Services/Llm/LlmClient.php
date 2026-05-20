<?php

declare(strict_types=1);

namespace App\Services\Llm;

use App\Models\LlmSetting;
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

        if (! $cfg['enabled'] || blank($cfg['api_key'])) {
            throw new LlmException('LLM integration is not configured. Open Filament > Настройки LLM.');
        }

        $payload = [
            'model' => $cfg['model'],
            'temperature' => $temperature ?? $cfg['temperature'],
            'max_tokens' => $maxTokens ?? $cfg['max_tokens'],
            'messages' => $messages,
        ];

        if ($expectsJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken($cfg['api_key'])
                ->timeout(60)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($cfg['base_url'], '/') . '/chat/completions', $payload);
        } catch (Throwable $e) {
            throw new LlmException("LLM request failed: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new LlmException("LLM API HTTP {$response->status()}: {$response->body()}");
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new LlmException('LLM returned empty content');
        }

        if (! $expectsJson) {
            return $content;
        }

        $parsed = json_decode($content, associative: true);

        if (! is_array($parsed)) {
            throw new LlmException('LLM JSON output could not be parsed: ' . substr($content, 0, 200));
        }

        return $parsed;
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
     * @return array{provider:string, base_url:string, api_key:?string, model:string, temperature:float, max_tokens:int, enabled:bool}
     */
    private function resolveConfig(): array
    {
        $setting = LlmSetting::current();

        if ($setting->enabled && filled($setting->api_key)) {
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

        return [
            'provider' => $fallback['provider'],
            'base_url' => $fallback['base_url'],
            'api_key' => $fallback['api_key'],
            'model' => $fallback['model'],
            'temperature' => (float) $fallback['temperature'],
            'max_tokens' => (int) $fallback['max_tokens'],
            'enabled' => filled($fallback['api_key']),
        ];
    }
}
