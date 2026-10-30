<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Support;

/**
 * Site and secret key pair used to render and verify the Turnstile widget.
 */
final readonly class TurnstileCredentials
{
    public function __construct(
        public string $siteKey,
        public string $secretKey,
    ) {}
}
