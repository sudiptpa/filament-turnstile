<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Contracts;

use Sujip\Filament\Turnstile\Support\TurnstileVerificationResult;

/**
 * Resolves Turnstile credentials and sends verification requests to Cloudflare.
 */
interface TurnstileClientContract
{
    public function siteKey(): ?string;

    public function isConfigured(): bool;

    public function verify(string $token): TurnstileVerificationResult;
}
