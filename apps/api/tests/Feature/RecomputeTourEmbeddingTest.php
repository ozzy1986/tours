<?php

declare(strict_types=1);

use App\Jobs\RecomputeTourEmbedding;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.embeddings.url' => 'http://embeddings.test',
        'services.embeddings.dim' => 384,
        'services.embeddings.timeout' => 5,
        'services.embeddings.api_key' => 'embed-secret',
    ]);
});

it('skips embedding recompute on sqlite', function (): void {
    Http::fake();

    $tour = Tour::withoutEvents(fn () => Tour::factory()->create());

    RecomputeTourEmbedding::dispatchSync($tour->id);

    Http::assertNothingSent();
});

it('calls embeddings service with api key on pgsql', function (): void {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL (group pgsql).');
    }

    Http::fake([
        'embeddings.test/embed*' => Http::response(['vectors' => [array_fill(0, 384, 0.15)]], 200),
    ]);

    $tour = Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Pgvector Tour',
        'summary' => 'Summary',
        'description' => 'Description body',
    ]));

    RecomputeTourEmbedding::dispatchSync($tour->id);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Api-Key', 'embed-secret')
            && str_contains($request->url(), '/embed');
    });
})->group('pgsql');
