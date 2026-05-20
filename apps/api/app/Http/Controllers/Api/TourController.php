<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Filters\TourFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ToursIndexRequest;
use App\Http\Resources\TourResource;
use App\Http\Resources\TourSummaryResource;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TourController extends Controller
{
    public function __construct(private readonly TourFilter $filter) {}

    public function index(ToursIndexRequest $request): AnonymousResourceCollection
    {
        $query = Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures']);

        $query = $this->filter->apply($query, $request->validated());

        $perPage = (int) $request->validated('per_page', 12);

        return TourSummaryResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function show(string $slug): TourResource|JsonResponse
    {
        $tour = Tour::query()
            ->published()
            ->with(['categories', 'photos', 'departures' => fn ($q) => $q->orderBy('starts_on')])
            ->where('slug', $slug)
            ->first();

        if (! $tour) {
            return response()->json(['message' => 'Tour not found'], 404);
        }

        return TourResource::make($tour);
    }

    public function featured(): AnonymousResourceCollection
    {
        $tours = Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return TourSummaryResource::collection($tours);
    }
}
