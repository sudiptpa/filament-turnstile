<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Contracts;

use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

/**
 * Resolves the Turnstile site and secret key pair from wherever the application stores them.
 */
interface TurnstileKeyResolverContract
{
    public function credentials(): ?TurnstileCredentials;
}
