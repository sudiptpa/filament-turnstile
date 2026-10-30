<?php

declare(strict_types=1);

namespace Workbench\App\Support;

use Illuminate\Support\Facades\Cache;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Support\TurnstileVerificationResult;

/**
 * Stands in for Cloudflare in browser tests. Like the real service it accepts
 * a token once and rejects any later use of the same token.
 */
final class FakeTurnstileClient implements TurnstileClientContract
{
    public function siteKey(): string
    {
        return 'browser-test-site-key';
    }

    public function isConfigured(): bool
    {
        $port = $_SERVER['SERVER_PORT'] ?? getenv('E2E_PORT') ?: '8765';

        return ! file_exists(dirname(__DIR__, 3)."/build/browser-{$port}.unconfigured");
    }

    public function verify(string $token): TurnstileVerificationResult
    {
        Cache::add('e2e.verify_calls', 0);
        Cache::increment('e2e.verify_calls');

        $consumedKey = 'e2e.token.'.sha1($token);
        $accepted = str_starts_with($token, 'stub-') && ! Cache::has($consumedKey);

        Cache::forever($consumedKey, true);

        return new TurnstileVerificationResult($accepted, $accepted ? [] : ['invalid-input-response']);
    }
}
