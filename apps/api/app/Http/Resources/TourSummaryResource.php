<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tour */
class TourSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $minPrice = $this->whenLoaded('departures', function () {
            return $this->departures->min('price_cents');
        });

        $cover = $this->cover_url ?: optional($this->whenLoaded('photos', fn () => $this->photos->first()))?->url;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'duration_days' => $this->duration_days,
            'cover_url' => $cover,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'min_price_cents' => $minPrice,
            'currency' => 'RUB',
        ];
    }
}
