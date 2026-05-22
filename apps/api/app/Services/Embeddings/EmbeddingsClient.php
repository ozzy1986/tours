<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmbeddingsClient
{
    private const string HEALTH_CACHE_KEY = 'embeddings:health';

    /** TTL for a confirmed healthy state. */
    private const int HEALTH_CACHE_TTL_OK = 60;

    /** Shorter TTL for a degraded state so the service recovers within seconds of being fixed. */
    private const int HEALTH_CACHE_TTL_DEGRADED = 5;

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $dimension,
        private readonly int $timeout,
        private readonly ?string $apiKey = null,
    ) {}

    /**
     * Embed a list of texts. Returns a list of float vectors.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embed(array $texts, string $prefix = 'passage'): array
    {
        if ($texts === []) {
            return [];
        }

        try {
            $request = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson();

            if (filled($this->apiKey)) {
                $request = $request->withHeaders(['X-Api-Key' => $this->apiKey]);
            }

            $response = $request->post(rtrim($this->baseUrl, '/') . "/embed?prefix={$prefix}", [
                'texts' => array_values($texts),
            ]);
        } catch (Throwable $e) {
            throw new EmbeddingsException("Embeddings service unreachable: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new EmbeddingsException(
                "Embeddings service responded with HTTP {$response->status()}: {$response->body()}"
            );
        }

        $payload = $response->json();
        $vectors = $payload['vectors'] ?? null;

        if (! is_array($vectors) || count($vectors) !== count($texts)) {
            throw new EmbeddingsException('Embeddings service returned malformed payload');
        }

        foreach ($vectors as $i => $vec) {
            if (! is_array($vec) || count($vec) !== $this->dimension) {
                throw new EmbeddingsException(
                    "Vector {$i} has wrong dimension; expected {$this->dimension}"
                );
            }
        }

        return $vectors;
    }

    public function isHealthy(): bool
    {
        return ($this->health()['ok'] ?? false) === true;
    }

    /**
     * True when the embeddings service produces real semantic vectors AND the
     * model is actually loaded. Stub mode (hash-based vectors) and "not yet
     * loaded" states both yield useless similarities, so callers must skip
     * vector ranking and rely on keyword search instead.
     */
    public function isSemantic(): bool
    {
        $health = $this->health();

        return ($health['ok'] ?? false) === true
            && ($health['use_stub'] ?? true) === false
            && ($health['model_loaded'] ?? false) === true;
    }

    /** @return array{ok: bool, use_stub: bool, model_loaded: bool} */
    private function health(): array
    {
        /** @var array{ok: bool, use_stub: bool, model_loaded: bool}|null $cached */
        $cached = Cache::get(self::HEALTH_CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $health = $this->fetchHealth();
        $ttl = $health['ok'] && $health['model_loaded'] && ! $health['use_stub']
            ? self::HEALTH_CACHE_TTL_OK
            : self::HEALTH_CACHE_TTL_DEGRADED;

        Cache::put(self::HEALTH_CACHE_KEY, $health, $ttl);

        return $health;
    }

    /** @return array{ok: bool, use_stub: bool, model_loaded: bool} */
    private function fetchHealth(): array
    {
        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->get(rtrim($this->baseUrl, '/') . '/healthz');
        } catch (Throwable $e) {
            Log::warning('Embeddings health check failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'use_stub' => true, 'model_loaded' => false];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'use_stub' => true, 'model_loaded' => false];
        }

        return [
            'ok' => true,
            // Older service versions don't expose the flags; assume worst case.
            'use_stub' => (bool) ($response->json('use_stub') ?? true),
            'model_loaded' => (bool) ($response->json('model_loaded') ?? false),
        ];
    }

    /** @param  array<int, float>  $vector */
    public static function encodeForPg(array $vector): string
    {
        return '[' . implode(',', array_map(static fn ($v) => (string) (float) $v, $vector)) . ']';
    }
}
