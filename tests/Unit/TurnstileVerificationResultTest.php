<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\Filament\Turnstile\Support\TurnstileVerificationResult;

final class TurnstileVerificationResultTest extends TestCase
{
    public function test_successful_result(): void
    {
        $result = TurnstileVerificationResult::fromPayload(['success' => true, 'error-codes' => []]);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame([], $result->errorCodes);
    }

    public function test_failed_result(): void
    {
        $result = TurnstileVerificationResult::fromPayload([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function test_filters_non_string_error_codes(): void
    {
        $result = TurnstileVerificationResult::fromPayload([
            'success' => false,
            'error-codes' => ['code-1', 42, null, 'code-2'],
        ]);

        $this->assertSame(['code-1', 'code-2'], $result->errorCodes);
    }

    public function test_missing_payload_keys_default_to_safe_values(): void
    {
        $result = TurnstileVerificationResult::fromPayload([]);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame([], $result->errorCodes);
    }

    public function test_non_array_error_codes_default_to_empty(): void
    {
        $result = TurnstileVerificationResult::fromPayload([
            'success' => true,
            'error-codes' => 'not-an-array',
        ]);

        $this->assertSame([], $result->errorCodes);
    }
}
