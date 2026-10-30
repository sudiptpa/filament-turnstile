<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\User;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'MFA User', 'email' => 'mfa@example.test', 'password' => 'password', 'mfa' => true]);
        User::create(['name' => 'Other MFA User', 'email' => 'other-mfa@example.test', 'password' => 'password', 'mfa' => true]);
        User::create(['name' => 'Plain User', 'email' => 'plain@example.test', 'password' => 'password', 'mfa' => false]);
    }
}
