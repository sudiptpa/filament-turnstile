<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

final class TurnstileCredentialsTest extends TestCase
{
    public function test_it_stores_site_key_and_secret_key(): void
    {
        $credentials = new TurnstileCredentials('site-key', 'secret-key');

        $this->assertSame('site-key', $credentials->siteKey);
        $this->assertSame('secret-key', $credentials->secretKey);
    }
}
