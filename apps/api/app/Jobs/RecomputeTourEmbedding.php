<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tour;
use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecomputeTourEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $tourId) {}

    public function handle(EmbeddingsClient $client): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tour = Tour::find($this->tourId);

        if (! $tour) {
            return;
        }

        $text = trim($tour->title . "\n\n" . $tour->summary . "\n\n" . $tour->description);

        if ($text === '') {
            return;
        }

        try {
            $vectors = $client->embed([$text], prefix: 'passage');
        } catch (EmbeddingsException $e) {
            Log::warning('Failed to recompute embedding', [
                'tour_id' => $this->tourId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        DB::update(
            'UPDATE tours SET embedding = ?::vector WHERE id = ?',
            [EmbeddingsClient::encodeForPg($vectors[0]), $this->tourId],
        );
    }
}
