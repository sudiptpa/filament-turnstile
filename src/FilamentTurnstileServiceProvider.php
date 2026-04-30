<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Contracts\TurnstileKeyResolverContract;
use Sujip\Filament\Turnstile\Resolvers\ConfigKeyResolver;
use Sujip\Filament\Turnstile\Rules\TurnstileRule;
use Sujip\Filament\Turnstile\Support\TurnstileClient;

final class FilamentTurnstileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-turnstile.php',
            'filament-turnstile',
        );

        $this->app->bind(TurnstileKeyResolverContract::class, ConfigKeyResolver::class);

        $this->app->bind(TurnstileClientContract::class, TurnstileClient::class);

        $this->app->bind(TurnstileRule::class, fn (): TurnstileRule => new TurnstileRule());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-turnstile');

        Blade::component('filament-turnstile::components.widget', 'turnstile');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filament-turnstile.php' => config_path('filament-turnstile.php'),
            ], 'filament-turnstile-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/filament-turnstile'),
            ], 'filament-turnstile-views');
        }
    }
}
