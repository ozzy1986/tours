<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Events\ServingFilament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FilamentLocaleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $locale = (string) config('app.locale', 'ru');

        app()->setLocale($locale);

        Event::listen(ServingFilament::class, function () use ($locale): void {
            app()->setLocale($locale);
        });
    }
}
