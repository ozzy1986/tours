<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new \Database\Seeders\CategorySeeder)->run();
});

it('lists published tours', function () {
    $cat = Category::first();
    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'test-tour',
        'title' => 'Test Tour',
        'summary' => 'Summary text here for the tour card.',
        'description' => 'Long description',
        'duration_days' => 5,
        'published_at' => now(),
    ]))->categories()->attach($cat);

    $response = $this->getJson('/api/tours');

    $response->assertOk()
        ->assertJsonPath('data.0.slug', 'test-tour');
});

it('returns tour by slug', function () {
    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'my-tour',
        'title' => 'My Tour',
        'summary' => 'Short summary for SEO and cards.',
        'description' => 'Body',
        'duration_days' => 3,
        'published_at' => now(),
    ]));

    $this->getJson('/api/tours/my-tour')
        ->assertOk()
        ->assertJsonPath('data.slug', 'my-tour');
});

it('lists categories', function () {
    $this->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('excludes unpublished tours', function () {
    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'draft-tour',
        'published_at' => null,
    ]));

    $this->getJson('/api/tours')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters by a single category query param', function () {
    $cat = Category::where('slug', 'beach')->first();
    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'beach-tour',
        'published_at' => now(),
    ]))->categories()->attach($cat);

    Tour::withoutEvents(fn () => Tour::factory()->create([
        'slug' => 'other-tour',
        'published_at' => now(),
    ]))->categories()->attach(Category::where('slug', 'city')->first());

    $this->getJson('/api/tours?category=beach')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'beach-tour');
});

it('returns featured tours', function () {
    Tour::withoutEvents(fn () => Tour::factory()->count(2)->create([
        'published_at' => now(),
    ]));

    $this->getJson('/api/tours/featured')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns 404 for unknown tour slug', function () {
    $this->getJson('/api/tours/unknown-slug')
        ->assertNotFound()
        ->assertJsonPath('message', 'Tour not found');
});
