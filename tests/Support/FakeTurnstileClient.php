<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Support;

use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Support\TurnstileVerificationResult;

final class FakeTurnstileClient implements TurnstileClientContract
{
    public int $verifyCalls = 0;

    /** @var list<string> */
    public array $consumed = [];

    public bool $configured = true;

    public function siteKey(): string
    {
        return 'site-key';
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Tokens are single-use, like Cloudflare's.
     */
    public function verify(string $token): TurnstileVerificationResult
    {
        $this->verifyCalls++;

        $ok = $token !== 'bad' && ! in_array($token, $this->consumed, true);
        $this->consumed[] = $token;

        return new TurnstileVerificationResult($ok, $ok ? [] : ['invalid-input-response']);
    }
}
