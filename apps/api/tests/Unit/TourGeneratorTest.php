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
