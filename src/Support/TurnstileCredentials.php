<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Support;

final readonly class TurnstileCredentials
{
    public function __construct(
        public string $siteKey,
        public string $secretKey,
    ) {}
}
