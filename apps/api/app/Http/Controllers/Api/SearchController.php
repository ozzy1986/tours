<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Http\Resources\TourSummaryResource;
use App\Models\Tour;
use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchController extends Controller
{
    private const float HYBRID_KEYWORD_WEIGHT = 0.65;

    private const float HYBRID_VECTOR_WEIGHT = 0.35;

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

        [$tours, $mode] = $this->hybridSearch($query, $vectors[0], $limit);

        return response()->json([
            'data' => $this->resolveSummaries($tours),
            'meta' => ['query' => $query, 'mode' => $mode, 'count' => $tours->count()],
        ]);
    }

    private function supportsVectorSearch(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            && Schema::hasColumn('tours', 'embedding');
    }

    private function supportsPgTrgm(): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $row = DB::selectOne(
                "SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm' LIMIT 1"
            );

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Merge keyword hits with vector similarity when both are available.
     *
     * @param  array<int, float>  $vector
     * @return array{0: Collection<int, Tour>, 1: string}
     */
    private function hybridSearch(string $query, array $vector, int $limit): array
    {
        $keywordTours = $this->keywordSearch($query, $limit * 3);

        if ($keywordTours->isEmpty()) {
            return [$this->vectorSearch($vector, $limit), 'semantic'];
        }

        $vectorTours = $this->vectorSearch($vector, $limit * 3);

        return [
            $this->mergeHybridResults($keywordTours, $vectorTours, $limit),
            'hybrid',
        ];
    }

    /**
     * @param  Collection<int, Tour>  $keywordTours
     * @param  Collection<int, Tour>  $vectorTours
     * @return Collection<int, Tour>
     */
    private function mergeHybridResults(Collection $keywordTours, Collection $vectorTours, int $limit): Collection
    {
        /** @var array<int, float> $scores */
        $scores = [];
        /** @var array<int, Tour> $toursById */
        $toursById = [];

        foreach ($keywordTours as $tour) {
            $kw = (float) ($tour->getAttribute('keyword_score') ?? 1.0);
            $scores[$tour->id] = ($scores[$tour->id] ?? 0.0) + self::HYBRID_KEYWORD_WEIGHT * min(1.0, max(0.0, $kw));
            $toursById[$tour->id] = $tour;
        }

        foreach ($vectorTours as $tour) {
            $dist = (float) ($tour->getAttribute('distance') ?? 1.0);
            $vec = 1.0 / (1.0 + max(0.0, $dist));
            $scores[$tour->id] = ($scores[$tour->id] ?? 0.0) + self::HYBRID_VECTOR_WEIGHT * $vec;
            $toursById[$tour->id] = $tour;
        }

        arsort($scores);

        return collect(array_slice(array_keys($scores), 0, $limit))
            ->map(fn (int $id) => $toursById[$id])
            ->values();
    }

    /** @return Collection<int, Tour> */
    private function keywordSearch(string $query, int $limit): Collection
    {
        if ($this->supportsPgTrgm()) {
            return $this->keywordSearchPgTrgm($query, $limit);
        }

        return $this->keywordSearchLike($query, $limit);
    }

    /** @return Collection<int, Tour> */
    private function keywordSearchPgTrgm(string $query, int $limit): Collection
    {
        $terms = $this->searchTerms($query);
        $expr = $this->searchableTextExpression();
        $needle = mb_strtolower(trim($query), 'UTF-8');

        return $this->publishedTourQuery()
            ->where(function ($q) use ($terms, $expr, $needle) {
                foreach ($terms as $term) {
                    $t = mb_strtolower($term, 'UTF-8');
                    $q->orWhereRaw("{$expr} % ?", [$t])
                        ->orWhereRaw("similarity({$expr}, ?) > 0.05", [$t]);
                }
                if ($needle !== '') {
                    $q->orWhereRaw("similarity({$expr}, ?) > 0.05", [$needle]);
                }
            })
            ->selectRaw(
                "tours.*, GREATEST(similarity({$expr}, ?), 0.05) AS keyword_score",
                [$needle !== '' ? $needle : ' ']
            )
            ->orderByDesc('keyword_score')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Tour> */
    private function keywordSearchLike(string $query, int $limit): Collection
    {
        $terms = $this->searchTerms($query);

        return $this->publishedTourQuery()
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere(function ($sub) use ($term) {
                        foreach ($this->termLikePatterns($term) as $pattern) {
                            $sub->orWhere('title', 'like', $pattern)
                                ->orWhere('summary', 'like', $pattern)
                                ->orWhere('description', 'like', $pattern);
                        }
                    });
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    private function searchableTextExpression(): string
    {
        return "lower(title || ' ' || coalesce(summary, '') || ' ' || coalesce(description, ''))";
    }

    /** @return list<string> */
    private function termLikePatterns(string $term): array
    {
        $lower = mb_strtolower($term, 'UTF-8');
        $variants = array_filter([$term, $lower], static fn (string $v) => $v !== '');

        if ($lower !== $term && mb_strlen($lower, 'UTF-8') > 0) {
            $variants[] = mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_substr($lower, 1, null, 'UTF-8');
        }

        return array_values(array_unique(array_map(
            static fn (string $v) => '%' . $v . '%',
            $variants
        )));
    }

    /** @return list<string> */
    private function searchTerms(string $query): array
    {
        $terms = array_filter([trim($query)], fn ($t) => $t !== '');

        $aliases = [
            'крым' => ['крым', 'Крым', 'Севастополь', 'Балаклава', 'Крымский'],
            'байкал' => ['байкал', 'Байкал', 'Байкала', 'Листвянка', 'Ольхон'],
            'алтай' => ['алтай', 'Алтай', 'Горный Алтай'],
        ];

        $lower = mb_strtolower($query, 'UTF-8');
        if (isset($aliases[$lower])) {
            return array_values(array_unique($aliases[$lower]));
        }

        return $terms;
    }

    /**
     * @param  array<int, float>  $embedding
     * @param  list<int>  $excludeIds
     * @return Collection<int, Tour>
     */
    private function vectorSearch(array $embedding, int $limit, array $excludeIds = []): Collection
    {
        $vector = EmbeddingsClient::encodeForPg($embedding);
        $query = $this->publishedTourQuery()->whereNotNull('embedding');

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query
            ->selectRaw('tours.*, embedding <=> ?::vector AS distance', [$vector])
            ->orderByRaw('embedding <=> ?::vector', [$vector])
            ->limit($limit)
            ->get();
    }

    /** @return Builder<Tour> */
    private function publishedTourQuery(): Builder
    {
        return Tour::query()
            ->published()
            ->with([
                'categories',
                'photos' => fn ($q) => $q->orderBy('position')->limit(1),
                'departures',
            ]);
    }

    /** @param  Collection<int, Tour>  $tours
     * @return array<int, array<string, mixed>>
     */
    private function resolveSummaries(Collection $tours): array
    {
        return TourSummaryResource::collection($tours)->resolve();
    }
}
