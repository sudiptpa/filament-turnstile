<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Workbench\App\Models\User;
use Workbench\App\Support\FakeTurnstileClient;

final class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AdminPanelProvider::class);
        $this->app->singleton(TurnstileClientContract::class, FakeTurnstileClient::class);

        // The dev server does not pass custom env vars to PHP, so each server
        // (identified by its port) keeps its own database file under build/.
        $port = $_SERVER['SERVER_PORT'] ?? getenv('E2E_PORT') ?: '8765';

        config([
            'auth.providers.users.model' => User::class,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => dirname(__DIR__, 3)."/build/browser-{$port}.sqlite",
            'cache.default' => 'database',
            'app.url' => "http://127.0.0.1:{$port}",
            'session.driver' => 'file',
            'session.domain' => null,
            'session.secure' => false,
            'queue.default' => 'sync',
            'mail.default' => 'array',
        ]);

        // Drop any connection opened before the per-server path above was set.
        $this->app['db']->purge('sqlite');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Route::prefix('__e2e')->group(function (): void {
            Route::get('state', fn () => response()->json([
                'verifyCalls' => (int) Cache::get('e2e.verify_calls', 0),
            ]));

            Route::post('reset', function () {
                DB::table('cache')->delete();
                DB::table('cache_locks')->delete();

                return response()->noContent();
            });
        });
    }
}
