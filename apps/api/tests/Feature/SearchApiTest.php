<?php

declare(strict_types=1);

use App\Models\Tour;
use App\Services\Embeddings\EmbeddingsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.embeddings.url' => 'http://embeddings.test',
        'services.embeddings.dim' => 384,
        'services.embeddings.timeout' => 5,
    ]);
});

it('returns semantic search results when embeddings succeed', function () {
    $vector = array_fill(0, 384, 0.1);

    Http::fake([
        'embeddings.test/embed*' => Http::response(['vectors' => [$vector]], 200),
    ]);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Alpine Trek',
        'summary' => 'Mountain hiking adventure in the Alps.',
        'description' => 'Detailed alpine route.',
        'published_at' => now(),
    ]));

    $expectedMode = \Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'pgsql'
        ? 'semantic'
        : 'keyword';

    $this->postJson('/api/search', ['q' => 'mountain hike'])
        ->assertOk()
        ->assertJsonPath('meta.mode', $expectedMode)
        ->assertJsonPath('meta.query', 'mountain hike');
});

it('returns fallback results when embeddings fail', function () {
    Http::fake([
        'embeddings.test/embed*' => Http::response('unavailable', 503),
    ]);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Seaside Escape',
        'summary' => 'Relaxing beach holiday package.',
        'description' => 'Full beach description.',
        'published_at' => now(),
    ]));

    $response = $this->postJson('/api/search', ['q' => 'beach']);

    $response->assertStatus(503)
        ->assertJsonPath('meta', null)
        ->assertJsonCount(1, 'fallback');

    expect($response->json('fallback.0.title'))->toBe('Seaside Escape');
});
