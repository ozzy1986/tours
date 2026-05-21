<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LlmSetting;
use App\Services\Embeddings\EmbeddingsClient;
use App\Services\Embeddings\EmbeddingsException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
            TourSeeder::class,
        ]);

        LlmSetting::current();

        $this->recomputeEmbeddings();
    }

    /**
     * Pre-compute pgvector embeddings so semantic search works right after seeding.
     * Skipped when DB has no vector support (SQLite tests) or embeddings service is offline.
     */
    private function recomputeEmbeddings(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            app(EmbeddingsClient::class)->embed(['warmup'], prefix: 'query');
        } catch (EmbeddingsException $e) {
            $this->command?->warn(
                'Embeddings service unreachable, skipping vector backfill: ' . $e->getMessage()
            );

            return;
        }

        $this->command?->info('Computing tour embeddings (semantic search)...');
        Artisan::call('tours:embed-all', ['--sync' => true], $this->command?->getOutput());
    }
}
