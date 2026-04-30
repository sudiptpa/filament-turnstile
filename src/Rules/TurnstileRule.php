<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Exceptions\TurnstileException;

final class TurnstileRule implements ValidationRule
{
    private TurnstileClientContract $client;

    public function __construct(?TurnstileClientContract $client = null)
    {
        $this->client = $client ?? app(TurnstileClientContract::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Please complete the security challenge.');

            return;
        }

        try {
            $result = $this->client->verify($value);
        } catch (TurnstileException) {
            $fail('The security challenge could not be verified. Please try again.');

            return;
        }

        if (! $result->isSuccessful()) {
            $fail('The security challenge failed. Please try again.');
        }
    }
}
