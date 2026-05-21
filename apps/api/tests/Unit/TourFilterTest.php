<?php

declare(strict_types=1);

use App\Filters\TourFilter;
use App\Models\Category;
use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new \Database\Seeders\CategorySeeder)->run();
    $this->filter = new TourFilter;
});

it('filters tours by category slug', function (): void {
    $beach = Category::where('slug', 'beach')->first();
    $city = Category::where('slug', 'city')->first();

    $beachTour = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    $beachTour->categories()->attach($beach);

    $cityTour = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    $cityTour->categories()->attach($city);

    $ids = $this->filter
        ->apply(Tour::query(), ['category' => ['beach']])
        ->pluck('id')
        ->all();

    expect($ids)->toContain($beachTour->id)->not->toContain($cityTour->id);
});

it('filters tours by price range via departures', function (): void {
    $cheap = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $cheap->id,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-07',
        'price_cents' => 5_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $expensive = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $expensive->id,
        'starts_on' => '2026-07-01',
        'ends_on' => '2026-07-07',
        'price_cents' => 700_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $ids = $this->filter
        ->apply(Tour::query(), ['price_min' => 40, 'price_max' => 60])
        ->pluck('id')
        ->all();

    expect($ids)->toContain($cheap->id)->not->toContain($expensive->id);
});

it('filters tours by departure date range', function (): void {
    $early = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $early->id,
        'starts_on' => '2026-05-01',
        'ends_on' => '2026-05-10',
        'price_cents' => 100_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $late = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $late->id,
        'starts_on' => '2026-09-01',
        'ends_on' => '2026-09-10',
        'price_cents' => 100_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $ids = $this->filter
        ->apply(Tour::query(), ['date_from' => '2026-08-01', 'date_to' => '2026-09-30'])
        ->pluck('id')
        ->all();

    expect($ids)->not->toContain($early->id)->toContain($late->id);
});

it('sorts tours by ascending price', function (): void {
    $low = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $low->id,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-07',
        'price_cents' => 3_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $high = Tour::withoutEvents(fn () => Tour::factory()->create(['published_at' => now()]));
    TourDeparture::create([
        'tour_id' => $high->id,
        'starts_on' => '2026-06-01',
        'ends_on' => '2026-06-07',
        'price_cents' => 9_000,
        'seats_total' => 10,
        'seats_available' => 10,
    ]);

    $ordered = $this->filter
        ->apply(Tour::query()->whereIn('id', [$low->id, $high->id]), ['sort' => 'price_asc'])
        ->pluck('id')
        ->all();

    expect($ordered)->toBe([$low->id, $high->id]);
});

it('sorts tours by duration descending', function (): void {
    $short = Tour::withoutEvents(fn () => Tour::factory()->create([
        'duration_days' => 3,
        'published_at' => now(),
    ]));
    $long = Tour::withoutEvents(fn () => Tour::factory()->create([
        'duration_days' => 12,
        'published_at' => now(),
    ]));

    $ordered = $this->filter
        ->apply(Tour::query()->whereIn('id', [$short->id, $long->id]), ['sort' => 'duration_desc'])
        ->pluck('id')
        ->all();

    expect($ordered)->toBe([$long->id, $short->id]);
});
