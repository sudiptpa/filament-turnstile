<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Resolvers;

use Sujip\Filament\Turnstile\Contracts\TurnstileKeyResolverContract;
use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

/**
 * Reads Turnstile credentials from the published config file.
 */
final class ConfigKeyResolver implements TurnstileKeyResolverContract
{
    public function credentials(): ?TurnstileCredentials
    {
        /** @var mixed $siteKey */
        $siteKey = config('filament-turnstile.site_key');

        /** @var mixed $secretKey */
        $secretKey = config('filament-turnstile.secret_key');

        if (! is_string($siteKey) || $siteKey === '' || ! is_string($secretKey) || $secretKey === '') {
            return null;
        }

        return new TurnstileCredentials($siteKey, $secretKey);
    }
}
