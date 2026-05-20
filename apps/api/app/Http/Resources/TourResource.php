<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tour */
class TourResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
            'cover_url' => $this->cover_url ?: optional($this->photos->first())?->url,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'route_geojson' => $this->route_geojson,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'photos' => TourPhotoResource::collection($this->whenLoaded('photos')),
            'departures' => TourDepartureResource::collection($this->whenLoaded('departures')),
        ];
    }
}
