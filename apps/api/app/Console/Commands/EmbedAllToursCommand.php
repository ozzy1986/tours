<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecomputeTourEmbedding;
use App\Models\Tour;
use Illuminate\Console\Command;

class EmbedAllToursCommand extends Command
{
    protected $signature = 'tours:embed-all {--sync : Run synchronously instead of queue}';

    protected $description = 'Recompute pgvector embeddings for all tours';

    public function handle(): int
    {
        $ids = Tour::query()->pluck('id');
        $bar = $this->output->createProgressBar($ids->count());
        $bar->start();

        foreach ($ids as $id) {
            if ($this->option('sync')) {
                RecomputeTourEmbedding::dispatchSync($id);
            } else {
                RecomputeTourEmbedding::dispatch($id);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Queued embedding jobs for {$ids->count()} tours.");

        return self::SUCCESS;
    }
}
