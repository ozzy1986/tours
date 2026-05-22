<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\LlmSetting;
use App\Services\Llm\TourGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new \Database\Seeders\CategorySeeder)->run();

    LlmSetting::query()->delete();

    $setting = new LlmSetting();
    $setting->id = 1;
    $setting->provider = 'openai';
    $setting->base_url = 'https://api.test/v1';
    $setting->api_key = 'test-key';
    $setting->model = 'gpt-test';
    $setting->temperature = 0.5;
    $setting->max_tokens = 1024;
    $setting->enabled = true;
    $setting->save();
});

it('normalizes llm json into tour shape', function (): void {
    Http::fake([
        'api.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'title' => 'Тур по Крыму',
                        'slug' => 'INVALID SLUG!!!',
                        'summary' => 'Кратко',
                        'description' => 'Подробно',
                        'duration_days' => 99,
                        'category_slugs' => ['beach', 'invalid-cat'],
                        'route_waypoints' => [
                            ['name' => 'Севастополь', 'lat' => 44.6, 'lng' => 33.5],
                            ['name' => 'skip', 'lat' => 0, 'lng' => 0],
                        ],
                        'departures' => [
                            [
                                'starts_on' => '2026-07-01',
                                'ends_on' => '2026-07-07',
                                'price_rub' => 45000,
                                'seats_total' => 8,
                            ],
                            ['starts_on' => 'bad', 'ends_on' => 'dates'],
                        ],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]],
        ], 200),
    ]);

    $result = app(TourGenerator::class)->generate('Сделай тур по Крыму');

    expect($result['title'])->toBe('Тур по Крыму')
        ->and($result['duration_days'])->toBe(21)
        ->and($result['category_slugs'])->toBe(['beach'])
        ->and($result['route_waypoints'])->toHaveCount(1)
        ->and($result['departures'])->toHaveCount(1)
        ->and($result['departures'][0]['price_rub'])->toBe(45000);

    expect($result['slug'])->toMatch('/^[a-z0-9-]+$/');
});

it('uses category slugs from database only', function (): void {
    $validSlug = Category::query()->value('slug');

    Http::fake([
        'api.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'title' => 'Test',
                        'summary' => 'Тестовая сводка',
                        'description' => 'Тестовое описание',
                        'category_slugs' => [$validSlug, 'nonexistent'],
                        'route_waypoints' => [],
                        'departures' => [],
                    ]),
                ],
            ]],
        ], 200),
    ]);

    $result = app(TourGenerator::class)->generate('prompt');

    expect($result['category_slugs'])->toBe([$validSlug]);
});

it('throws when the LLM returns empty/off-schema JSON', function (): void {
    Http::fake([
        'api.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '{}'],
            ]],
        ], 200),
    ]);

    app(TourGenerator::class)->generate('prompt');
})->throws(\App\Services\Llm\LlmException::class, 'пустой');

it('tolerates markdown-wrapped JSON from small models', function (): void {
    $validSlug = \App\Models\Category::query()->value('slug');

    $json = json_encode([
        'title' => 'Тур-обёртка',
        'summary' => 'Кратко',
        'description' => 'Подробно',
        'duration_days' => 5,
        'category_slugs' => [$validSlug],
        'route_waypoints' => [['name' => 'A', 'lat' => 55.0, 'lng' => 82.0]],
        'departures' => [],
    ], JSON_UNESCAPED_UNICODE);

    Http::fake([
        'api.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => "```json\n{$json}\n```"],
            ]],
        ], 200),
    ]);

    $result = app(TourGenerator::class)->generate('prompt');

    expect($result['title'])->toBe('Тур-обёртка')
        ->and($result['duration_days'])->toBe(5);
});

it('does not require an API key for Ollama provider', function (): void {
    \App\Models\LlmSetting::query()->update([
        'provider' => 'ollama',
        'base_url' => 'http://127.0.0.1:11434/v1',
        'api_key' => null,
    ]);

    Http::fake([
        '127.0.0.1:11434/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'title' => 'Локально',
                        'summary' => 'Кратко',
                        'description' => 'Подробно',
                        'duration_days' => 3,
                        'category_slugs' => [],
                        'route_waypoints' => [],
                        'departures' => [],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]],
        ], 200),
    ]);

    $result = app(TourGenerator::class)->generate('prompt');

    expect($result['title'])->toBe('Локально');
});
