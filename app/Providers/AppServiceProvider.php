<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Schlanke, zum Design passende Seitennavigation für alle Listen
        // Slim pagination matching the design for all lists
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // SQLite: WAL-Modus für stabilere gleichzeitige Zugriffe
        // SQLite: WAL mode for more stable concurrent access
        if (config('database.default') === 'sqlite') {
            try {
                DB::statement('PRAGMA journal_mode=WAL;');
                DB::statement('PRAGMA busy_timeout=5000;');
            } catch (\Throwable) {
                // DB existiert beim ersten Start evtl. noch nicht
                // The DB may not exist yet on the first start
            }
        }
    }
}
