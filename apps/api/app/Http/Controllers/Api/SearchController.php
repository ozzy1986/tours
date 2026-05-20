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

    /**
     * Keyword matches when present; otherwise vector similarity only.
     *
     * @param  array<int, float>  $vector
     * @return array{0: Collection<int, Tour>, 1: string}
     */
    private function hybridSearch(string $query, array $vector, int $limit): array
    {
        $keywordTours = $this->keywordSearch($query, $limit);

        if ($keywordTours->isEmpty()) {
            return [$this->vectorSearch($vector, $limit), 'semantic'];
        }

        // Text matches only — do not dilute with irrelevant vector results (stub embeddings).
        return [$keywordTours, 'keyword'];
    }

    /** @return Collection<int, Tour> */
    private function keywordSearch(string $query, int $limit): Collection
    {
        $terms = $this->searchTerms($query);

        return $this->publishedTourQuery()
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere(function ($sub) use ($term) {
                        foreach ($this->termLikePatterns($term) as $pattern) {
                            $sub->orWhere(function ($inner) use ($pattern) {
                                $inner->where('title', 'like', $pattern)
                                    ->orWhere('summary', 'like', $pattern)
                                    ->orWhere('description', 'like', $pattern);
                            });
                        }

                        if (Schema::getConnection()->getDriverName() === 'pgsql') {
                            $needle = '%' . mb_strtolower($term, 'UTF-8') . '%';
                            $sub->orWhereRaw('LOWER(title) LIKE ?', [$needle])
                                ->orWhereRaw('LOWER(summary) LIKE ?', [$needle])
                                ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
                        }
                    });
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
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
        $query = $this->publishedTourQuery()->whereNotNull('embedding');

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderByEmbedding($embedding)->limit($limit)->get();
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
