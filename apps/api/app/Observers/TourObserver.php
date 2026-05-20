<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RecomputeTourEmbedding;
use App\Models\Tour;

class TourObserver
{
    public function saved(Tour $tour): void
    {
        $dirty = $tour->wasChanged(['title', 'summary', 'description']);

        if ($tour->wasRecentlyCreated || $dirty) {
            RecomputeTourEmbedding::dispatch($tour->id)->afterCommit();
        }
    }
}
