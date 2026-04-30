<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Contracts;

use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

interface TurnstileKeyResolverContract
{
    public function credentials(): ?TurnstileCredentials;
}
