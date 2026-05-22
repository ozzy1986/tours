<?php

declare(strict_types=1);

use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.embeddings.url' => 'http://embeddings.test',
        'services.embeddings.dim' => 384,
        'services.embeddings.timeout' => 5,
    ]);

    cache()->forget('embeddings:health');
});

/**
 * Default fake: the embeddings service is healthy and runs a real model.
 * Individual tests can replace it via Http::fake() before issuing requests.
 */
function fakeSemanticEmbeddings(array $vector): void
{
    Http::fake([
        'embeddings.test/healthz' => Http::response([
            'status' => 'ok',
            'model' => 'intfloat/multilingual-e5-small',
            'dim' => 384,
            'model_loaded' => true,
            'use_stub' => false,
        ], 200),
        'embeddings.test/embed*' => Http::response(['vectors' => [$vector]], 200),
    ]);
}

it('returns semantic search results when embeddings succeed', function () {
    $vector = array_fill(0, 384, 0.1);

    fakeSemanticEmbeddings($vector);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Alpine Trek',
        'summary' => 'Mountain hiking adventure in the Alps.',
        'description' => 'Detailed alpine route.',
        'published_at' => now(),
    ]));

    $expectedMode = Schema::getConnection()->getDriverName() === 'pgsql'
        && Schema::hasColumn('tours', 'embedding')
        ? 'semantic'
        : 'keyword';

    $this->postJson('/api/search', ['q' => 'zzzyyyxqw semantic only'])
        ->assertOk()
        ->assertJsonPath('meta.mode', $expectedMode)
        ->assertJsonPath('meta.query', 'zzzyyyxqw semantic only');
});

it('returns fallback results when embeddings fail', function () {
    Http::fake([
        'embeddings.test/healthz' => Http::response([
            'status' => 'ok',
            'model' => 'intfloat/multilingual-e5-small',
            'dim' => 384,
            'model_loaded' => true,
            'use_stub' => false,
        ], 200),
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

it('returns crimea tour first for lowercase Cyrillic query крым', function () {
    $vector = array_fill(0, 384, 0.1);

    fakeSemanticEmbeddings($vector);

    Tour::withoutEvents(function () {
        Tour::factory()->create([
            'title' => 'Исландия: кольцевая дорога',
            'summary' => 'Северная природа и водопады.',
            'description' => 'Кольцевой маршрут по Исландии.',
            'published_at' => now()->subDay(),
        ]);
        Tour::factory()->create([
            'title' => 'Эльбрус: восхождение с гидом',
            'summary' => 'Горный поход на Кавказе.',
            'description' => 'Восхождение на высочайшую вершину Европы.',
            'published_at' => now()->subDays(2),
        ]);
        Tour::factory()->create([
            'slug' => 'crimea-sevastopol',
            'title' => 'Крым: Севастополь и Балаклава',
            'summary' => 'Исторические места и море.',
            'description' => 'Экскурсии по Севастополю и Балаклаве.',
            'published_at' => now(),
        ]);
    });

    $expectedMode = Schema::getConnection()->getDriverName() === 'pgsql'
        && Schema::hasColumn('tours', 'embedding')
        ? 'hybrid'
        : 'keyword';

    $this->postJson('/api/search', ['q' => 'крым'])
        ->assertOk()
        ->assertJsonPath('meta.mode', $expectedMode)
        ->assertJsonPath('data.0.title', 'Крым: Севастополь и Балаклава');
});

it('returns crimea tour first for Cyrillic query Крым', function () {
    $vector = array_fill(0, 384, 0.1);

    fakeSemanticEmbeddings($vector);

    Tour::withoutEvents(function () {
        Tour::factory()->create([
            'title' => 'Исландия: кольцевая дорога',
            'summary' => 'Северная природа и водопады.',
            'description' => 'Кольцевой маршрут по Исландии.',
            'published_at' => now()->subDay(),
        ]);
        Tour::factory()->create([
            'title' => 'Эльбрус: восхождение с гидом',
            'summary' => 'Горный поход на Кавказе.',
            'description' => 'Восхождение на высочайшую вершину Европы.',
            'published_at' => now()->subDays(2),
        ]);
        Tour::factory()->create([
            'slug' => 'crimea-sevastopol',
            'title' => 'Крым: Севастополь и Балаклава',
            'summary' => 'Исторические места и море.',
            'description' => 'Экскурсии по Севастополю и Балаклаве.',
            'published_at' => now(),
        ]);
    });

    $response = $this->postJson('/api/search', ['q' => 'Крым']);

    $response->assertOk()
        ->assertJsonPath('data.0.title', 'Крым: Севастополь и Балаклава');

    $expectedMode = Schema::getConnection()->getDriverName() === 'pgsql'
        && Schema::hasColumn('tours', 'embedding')
        ? 'hybrid'
        : 'keyword';

    expect($response->json('meta.mode'))->toBe($expectedMode);
    expect($response->json('data.0.title'))->toContain('Крым');
});

it('matches lowercase крым against capitalized tour titles', function () {
    fakeSemanticEmbeddings(array_fill(0, 384, 0.1));

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'crimea-sevastopol',
        'title' => 'Крым: Севастополь и Балаклава',
        'summary' => 'Море и экскурсии.',
        'description' => 'Севастополь.',
        'published_at' => now(),
    ]));

    $this->postJson('/api/search', ['q' => 'крым'])
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'crimea-sevastopol')
        ->assertJsonPath('meta.mode', Schema::getConnection()->getDriverName() === 'pgsql'
            && Schema::hasColumn('tours', 'embedding')
            ? 'hybrid'
            : 'keyword')
        ->assertJsonCount(1, 'data');
});

it('skips vector search and returns empty for unmatched query when embeddings run in stub mode', function () {
    Http::fake([
        'embeddings.test/healthz' => Http::response([
            'status' => 'ok',
            'model' => 'stub',
            'dim' => 384,
            'model_loaded' => true,
            'use_stub' => true,
        ], 200),
        'embeddings.test/embed*' => Http::response(
            ['vectors' => [array_fill(0, 384, 0.1)]],
            200,
        ),
    ]);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Зимний Байкал',
        'summary' => 'Лёд, Ольхон и бурятское гостеприимство.',
        'description' => 'Ледяные гроты и мыс Хобой.',
        'published_at' => now(),
    ]));

    $response = $this->postJson('/api/search', ['q' => 'полетать на вертолёте']);

    $response->assertOk()
        ->assertJsonPath('meta.mode', 'keyword')
        ->assertJsonPath('meta.count', 0)
        ->assertJsonCount(0, 'data');

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '/embed?');
    });
});

it('falls back to keyword search when embeddings service is unreachable for health check', function () {
    Http::fake([
        'embeddings.test/*' => Http::response('down', 503),
    ]);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'title' => 'Seaside Escape',
        'summary' => 'Relaxing beach holiday package.',
        'description' => 'Full beach description.',
        'published_at' => now(),
    ]));

    $this->postJson('/api/search', ['q' => 'beach'])
        ->assertOk()
        ->assertJsonPath('meta.mode', 'keyword')
        ->assertJsonPath('data.0.title', 'Seaside Escape');
});
