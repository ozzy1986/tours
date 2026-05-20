<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\TourPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TourSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/tours.json');

        if (! File::exists($path)) {
            $this->command?->error("Missing {$path}");

            return;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

        $categories = Category::query()->pluck('id', 'slug');

        foreach ($rows as $row) {
            $slug = (string) $row['slug'];
            $waypoints = $row['route_waypoints'] ?? [];
            $geojson = $this->waypointsToGeoJson($waypoints);

            $tour = Tour::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $row['title'],
                    'summary' => $row['summary'],
                    'description' => $row['description'],
                    'duration_days' => (int) $row['duration_days'],
                    'route_geojson' => $geojson,
                    'cover_url' => $row['cover_url'] ?? null,
                    'meta_title' => $row['meta_title'] ?? $row['title'],
                    'meta_description' => $row['meta_description'] ?? $row['summary'],
                    'published_at' => Carbon::parse($row['published_at'] ?? 'now'),
                ],
            );

            $slugs = (array) ($row['category_slugs'] ?? []);
            $ids = collect($slugs)
                ->map(fn ($s) => $categories[$s] ?? null)
                ->filter()
                ->values()
                ->all();
            $tour->categories()->sync($ids);

            $tour->photos()->delete();
            foreach ((array) ($row['photos'] ?? []) as $i => $photo) {
                TourPhoto::create([
                    'tour_id' => $tour->id,
                    'url' => is_string($photo) ? $photo : $photo['url'],
                    'alt' => is_array($photo) ? ($photo['alt'] ?? $tour->title) : $tour->title,
                    'position' => $i,
                ]);
            }

            $tour->departures()->delete();
            foreach ((array) ($row['departures'] ?? []) as $dep) {
                $priceRub = (int) ($dep['price_rub'] ?? 0);
                $seats = (int) ($dep['seats_total'] ?? 16);
                TourDeparture::create([
                    'tour_id' => $tour->id,
                    'starts_on' => $dep['starts_on'],
                    'ends_on' => $dep['ends_on'],
                    'price_cents' => $priceRub * 100,
                    'currency' => 'RUB',
                    'seats_total' => $seats,
                    'seats_available' => (int) ($dep['seats_available'] ?? $seats),
                ]);
            }
        }

        $this->command?->info('Seeded ' . count($rows) . ' tours from tours.json');
    }

    /**
     * @param  list<array{name?:string, lat:float|int, lng:float|int}>  $waypoints
     * @return array<string, mixed>
     */
    private function waypointsToGeoJson(array $waypoints): array
    {
        $coords = [];
        $points = [];

        foreach ($waypoints as $wp) {
            $lat = (float) ($wp['lat'] ?? 0);
            $lng = (float) ($wp['lng'] ?? 0);
            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }
            $coords[] = [$lng, $lat];
            $points[] = [
                'name' => (string) ($wp['name'] ?? ''),
                'lat' => $lat,
                'lng' => $lng,
            ];
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coords,
            'waypoints' => $points,
        ];
    }
}
