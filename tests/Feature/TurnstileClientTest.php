<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Exceptions\MissingTurnstileCredentials;
use Sujip\Filament\Turnstile\Exceptions\TurnstileVerificationFailed;
use Sujip\Filament\Turnstile\Tests\TestCase;

final class TurnstileClientTest extends TestCase
{
    public function test_site_key_is_returned_from_config(): void
    {
        $client = $this->app->make(TurnstileClientContract::class);

        $this->assertSame('1x00000000000000000000AA', $client->siteKey());
    }

    public function test_is_configured_when_both_keys_are_set(): void
    {
        $client = $this->app->make(TurnstileClientContract::class);

        $this->assertTrue($client->isConfigured());
    }

    public function test_is_not_configured_when_keys_are_missing(): void
    {
        config(['filament-turnstile.site_key' => null]);
        config(['filament-turnstile.secret_key' => null]);

        $client = $this->app->make(TurnstileClientContract::class);

        $this->assertFalse($client->isConfigured());
    }

    public function test_verify_returns_successful_result(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true, 'error-codes' => []], 200),
        ]);

        $client = $this->app->make(TurnstileClientContract::class);
        $result = $client->verify('valid-token');

        $this->assertTrue($result->isSuccessful());
    }

    public function test_verify_returns_failed_result(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $client = $this->app->make(TurnstileClientContract::class);
        $result = $client->verify('bad-token');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function test_verify_sends_secret_key_and_token_in_request(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true, 'error-codes' => []], 200),
        ]);

        $client = $this->app->make(TurnstileClientContract::class);
        $client->verify('test-token');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['secret'] === '1x0000000000000000000000000000000AA'
                && $request['response'] === 'test-token';
        });
    }

    public function test_verify_throws_when_credentials_are_missing(): void
    {
        config(['filament-turnstile.site_key' => null]);
        config(['filament-turnstile.secret_key' => null]);

        $client = $this->app->make(TurnstileClientContract::class);

        $this->expectException(MissingTurnstileCredentials::class);

        $client->verify('any-token');
    }

    public function test_verify_throws_when_response_is_not_json(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response('not-json', 200),
        ]);

        $client = $this->app->make(TurnstileClientContract::class);

        $this->expectException(TurnstileVerificationFailed::class);

        $client->verify('some-token');
    }
}
