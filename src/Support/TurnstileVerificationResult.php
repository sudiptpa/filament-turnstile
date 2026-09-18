<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Support;

/**
 * Outcome of a Cloudflare Turnstile verification request.
 */
final readonly class TurnstileVerificationResult
{
    /**
     * @param  list<string>  $errorCodes
     */
    public function __construct(
        public bool $success,
        public array $errorCodes,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $errorCodes = $payload['error-codes'] ?? [];

        return new self(
            success: (bool) ($payload['success'] ?? false),
            errorCodes: is_array($errorCodes)
                ? array_values(array_filter($errorCodes, 'is_string'))
                : [],
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }
}
