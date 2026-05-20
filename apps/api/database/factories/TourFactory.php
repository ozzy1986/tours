<?php

namespace Database\Factories;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tour>
 */
class TourFactory extends Factory
{
    protected $model = Tour::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'slug' => Str::slug($title) . '-' . fake()->unique()->numerify('###'),
            'title' => $title,
            'summary' => fake()->paragraph(2),
            'description' => fake()->paragraphs(3, true),
            'duration_days' => fake()->numberBetween(3, 14),
            'route_geojson' => null,
            'cover_url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800',
            'published_at' => now(),
        ];
    }
}
