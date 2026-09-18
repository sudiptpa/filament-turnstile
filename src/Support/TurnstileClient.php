<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Contracts\TurnstileKeyResolverContract;
use Sujip\Filament\Turnstile\Exceptions\MissingTurnstileCredentials;
use Sujip\Filament\Turnstile\Exceptions\TurnstileVerificationFailed;

/**
 * Default Turnstile client, sends the verification request over HTTP with retries.
 */
final readonly class TurnstileClient implements TurnstileClientContract
{
    public function __construct(
        private Factory $http,
        private TurnstileKeyResolverContract $keyResolver,
    ) {}

    public function siteKey(): ?string
    {
        return $this->keyResolver->credentials()?->siteKey;
    }

    public function isConfigured(): bool
    {
        return $this->keyResolver->credentials() !== null;
    }

    public function verify(string $token): TurnstileVerificationResult
    {
        $credentials = $this->keyResolver->credentials();

        if ($credentials === null) {
            throw new MissingTurnstileCredentials();
        }

        /** @var string $verifyUrl */
        $verifyUrl = config('filament-turnstile.verify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');

        /** @var int $connectTimeout */
        $connectTimeout = config('filament-turnstile.connect_timeout', 5);

        /** @var int $timeout */
        $timeout = config('filament-turnstile.timeout', 10);

        try {
            $response = $this->http
                ->acceptJson()
                ->asForm()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->retry(3, 100)
                ->post($verifyUrl, [
                    'secret' => $credentials->secretKey,
                    'response' => $token,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new TurnstileVerificationFailed(
                'Cloudflare Turnstile verification request failed.',
                $exception->getCode(),
                previous: $exception,
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new TurnstileVerificationFailed('Cloudflare Turnstile returned an invalid response.');
        }

        return TurnstileVerificationResult::fromPayload($payload);
    }
}
