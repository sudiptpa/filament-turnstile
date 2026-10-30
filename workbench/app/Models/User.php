<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'mfa' => 'boolean'];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function hasEmailAuthentication(): bool
    {
        return (bool) $this->getAttribute('mfa');
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        $this->update(['mfa' => $condition]);
    }
}
