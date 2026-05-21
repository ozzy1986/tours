<?php

declare(strict_types=1);

use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Support\Facades\Http;

it('sends api key header when configured', function (): void {
    Http::fake([
        'embeddings.test/embed*' => Http::response(['vectors' => [array_fill(0, 384, 0.2)]], 200),
    ]);

    $client = new EmbeddingsClient(
        baseUrl: 'http://embeddings.test',
        dimension: 384,
        timeout: 5,
        apiKey: 'secret-key',
    );

    $vectors = $client->embed(['hello world'], prefix: 'passage');

    expect($vectors)->toHaveCount(1);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Api-Key', 'secret-key');
    });
});

it('throws when embeddings service returns error', function (): void {
    Http::fake([
        'embeddings.test/embed*' => Http::response('down', 503),
    ]);

    $client = new EmbeddingsClient('http://embeddings.test', 384, 5);

    expect(fn () => $client->embed(['text']))
        ->toThrow(EmbeddingsException::class);
});

it('encodes vectors for pgvector', function (): void {
    $encoded = EmbeddingsClient::encodeForPg([0.1, 0.2, 0.3]);

    expect($encoded)->toBe('[0.1,0.2,0.3]');
});
