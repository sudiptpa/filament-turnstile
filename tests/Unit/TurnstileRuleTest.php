<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Exceptions\TurnstileVerificationFailed;
use Sujip\Filament\Turnstile\Rules\TurnstileRule;
use Sujip\Filament\Turnstile\Support\TurnstileVerificationResult;

final class TurnstileRuleTest extends TestCase
{
    public function test_fails_when_token_is_empty_string(): void
    {
        $client = $this->createMock(TurnstileClientContract::class);
        $client->expects($this->never())->method('verify');

        $rule = new TurnstileRule($client);
        $failed = false;

        $rule->validate('turnstile_token', '', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_fails_when_token_is_not_a_string(): void
    {
        $client = $this->createMock(TurnstileClientContract::class);
        $client->expects($this->never())->method('verify');

        $rule = new TurnstileRule($client);
        $failed = false;

        $rule->validate('turnstile_token', null, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_fails_when_verification_throws(): void
    {
        $client = $this->createMock(TurnstileClientContract::class);
        $client->expects($this->once())
            ->method('verify')
            ->willThrowException(new TurnstileVerificationFailed('failed'));

        $rule = new TurnstileRule($client);
        $failed = false;

        $rule->validate('turnstile_token', 'some-token', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_fails_when_result_is_not_successful(): void
    {
        $result = new TurnstileVerificationResult(success: false, errorCodes: ['invalid-input-response']);

        $client = $this->createMock(TurnstileClientContract::class);
        $client->expects($this->once())
            ->method('verify')
            ->willReturn($result);

        $rule = new TurnstileRule($client);
        $failed = false;

        $rule->validate('turnstile_token', 'some-token', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_passes_when_verification_succeeds(): void
    {
        $result = new TurnstileVerificationResult(success: true, errorCodes: []);

        $client = $this->createMock(TurnstileClientContract::class);
        $client->expects($this->once())
            ->method('verify')
            ->willReturn($result);

        $rule = new TurnstileRule($client);
        $failed = false;

        $rule->validate('turnstile_token', 'valid-token', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }
}
