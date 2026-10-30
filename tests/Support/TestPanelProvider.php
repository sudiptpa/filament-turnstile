<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Support;

use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Panel;
use Filament\PanelProvider;
use Sujip\Filament\Turnstile\Pages\Auth\Login;

final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->multiFactorAuthentication([
                EmailAuthentication::make()->generateCodesUsing(fn (): string => '123456'),
            ]);
    }
}
