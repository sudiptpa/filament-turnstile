<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sujip\Filament\Turnstile\FilamentTurnstileServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /** @var \Illuminate\Foundation\Application */
    protected $app;
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FilamentTurnstileServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('filament-turnstile.site_key', '1x00000000000000000000AA');
        $app['config']->set('filament-turnstile.secret_key', '1x0000000000000000000000000000000AA');
    }
}
