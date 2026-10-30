<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Support;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TestUser extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'mfa' => 'boolean', 'blocked' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->blocked;
    }

    public function hasEmailAuthentication(): bool
    {
        return (bool) $this->mfa;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->update(['mfa' => $condition]);
    }
}
