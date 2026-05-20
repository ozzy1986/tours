<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Http\Resources\TourSummaryResource;
use App\Models\Tour;
use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, EmbeddingsClient $client): JsonResponse
    {
        $query = (string) $request->validated('q');
        $limit = (int) $request->validated('limit', 20);

        try {
            $vectors = $client->embed([$query], prefix: 'query');
        } catch (EmbeddingsException $e) {
            return response()->json([
                'message' => 'Semantic search is temporarily unavailable. Try filter-based search instead.',
                'fallback' => $this->fallbackResults($query, $limit),
            ], 503);
        }

        $tours = Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures'])
            ->orderByEmbedding($vectors[0])
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => TourSummaryResource::collection($tours)->resolve(),
            'meta' => ['query' => $query, 'mode' => 'semantic', 'count' => $tours->count()],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackResults(string $query, int $limit): array
    {
        $tours = Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'ilike', "%{$query}%")
                    ->orWhere('summary', 'ilike', "%{$query}%")
                    ->orWhere('description', 'ilike', "%{$query}%");
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return TourSummaryResource::collection($tours)->resolve();
    }
}
