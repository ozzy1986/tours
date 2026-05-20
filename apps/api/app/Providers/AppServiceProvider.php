<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Tour;
use App\Observers\TourObserver;
use App\Services\Embeddings\EmbeddingsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingsClient::class, function ($app) {
            $cfg = $app['config']->get('services.embeddings');

            return new EmbeddingsClient(
                baseUrl: $cfg['url'],
                dimension: (int) $cfg['dim'],
                timeout: (int) $cfg['timeout'],
            );
        });
    }

    public function boot(): void
    {
        Tour::observe(TourObserver::class);
    }
}
