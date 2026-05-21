<?php

declare(strict_types=1);

namespace App\Services\Embeddings;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmbeddingsClient
{
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
        try {
            return Http::timeout(3)->get(rtrim($this->baseUrl, '/') . '/healthz')->successful();
        } catch (Throwable $e) {
            Log::warning('Embeddings health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** @param  array<int, float>  $vector */
    public static function encodeForPg(array $vector): string
    {
        return '[' . implode(',', array_map(static fn ($v) => (string) (float) $v, $vector)) . ']';
    }
}
