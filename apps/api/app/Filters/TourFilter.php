<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;

class TourFilter
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Tour>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        if (! empty($filters['category'])) {
            $slugs = array_filter((array) $filters['category']);
            $query->whereHas('categories', fn ($q) => $q->whereIn('slug', $slugs));
        }

        if (! empty($filters['duration_min'])) {
            $query->where('duration_days', '>=', (int) $filters['duration_min']);
        }
        if (! empty($filters['duration_max'])) {
            $query->where('duration_days', '<=', (int) $filters['duration_max']);
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $min = (int) ($filters['price_min'] ?? 0) * 100;
            $max = (int) ($filters['price_max'] ?? PHP_INT_MAX / 100) * 100;
            $query->whereHas('departures', function ($q) use ($min, $max) {
                $q->whereBetween('price_cents', [$min, $max]);
            });
        }

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $query->whereHas('departures', function ($q) use ($filters) {
                if (! empty($filters['date_from'])) {
                    $q->where('starts_on', '>=', $filters['date_from']);
                }
                if (! empty($filters['date_to'])) {
                    $q->where('ends_on', '<=', $filters['date_to']);
                }
            });
        }

        return match ($filters['sort'] ?? null) {
            'newest' => $query->orderByDesc('published_at'),
            'price_asc' => $query->withMin('departures', 'price_cents')->orderBy('departures_min_price_cents'),
            'price_desc' => $query->withMin('departures', 'price_cents')->orderByDesc('departures_min_price_cents'),
            'duration_asc' => $query->orderBy('duration_days'),
            'duration_desc' => $query->orderByDesc('duration_days'),
            default => $query->orderByDesc('published_at'),
        };
    }
}
