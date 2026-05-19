<?php

namespace App\Providers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Observers\DispositionObserver;
use App\Observers\IncomingLetterObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        IncomingLetter::observe(IncomingLetterObserver::class);
        Disposition::observe(DispositionObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
