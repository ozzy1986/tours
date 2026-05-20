<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Http\Resources\TourSummaryResource;
use App\Models\Tour;
use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, EmbeddingsClient $client): JsonResponse
    {
        $query = (string) $request->validated('q');
        $limit = (int) $request->validated('limit', 20);

        try {
            $vectors = $client->embed([$query], prefix: 'query');
        } catch (EmbeddingsException) {
            return response()->json([
                'message' => 'Semantic search is temporarily unavailable. Try filter-based search instead.',
                'fallback' => $this->resolveSummaries($this->keywordSearch($query, $limit)),
            ], 503);
        }

        if (! $this->supportsVectorSearch()) {
            $tours = $this->keywordSearch($query, $limit);

            return response()->json([
                'data' => $this->resolveSummaries($tours),
                'meta' => ['query' => $query, 'mode' => 'keyword', 'count' => $tours->count()],
            ]);
        }

        $tours = Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures'])
            ->orderByEmbedding($vectors[0])
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $this->resolveSummaries($tours),
            'meta' => ['query' => $query, 'mode' => 'semantic', 'count' => $tours->count()],
        ]);
    }

    private function supportsVectorSearch(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            && Schema::hasColumn('tours', 'embedding');
    }

    /** @return Collection<int, Tour> */
    private function keywordSearch(string $query, int $limit): Collection
    {
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return Tour::query()
            ->published()
            ->with(['categories', 'photos' => fn ($q) => $q->orderBy('position')->limit(1), 'departures'])
            ->where(function ($q) use ($query, $operator) {
                $q->where('title', $operator, "%{$query}%")
                    ->orWhere('summary', $operator, "%{$query}%")
                    ->orWhere('description', $operator, "%{$query}%");
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /** @param  Collection<int, Tour>  $tours
     * @return array<int, array<string, mixed>>
     */
    private function resolveSummaries(Collection $tours): array
    {
        return TourSummaryResource::collection($tours)->resolve();
    }
}
