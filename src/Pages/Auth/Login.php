<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Exceptions\TurnstileException;

/**
 * Filament admin login page that adds a Turnstile challenge before authentication.
 */
class Login extends FilamentLogin
{
    public string $turnstileToken = '';

    public function form(Schema $schema): Schema
    {
        $client = app(TurnstileClientContract::class);

        if (! $client->isConfigured()) {
            return parent::form($schema);
        }

        return parent::form($schema)
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                View::make('filament-turnstile::turnstile-raw')
                    ->viewData(['siteKey' => $client->siteKey()]),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $client = app(TurnstileClientContract::class);

        if ($client->isConfigured()) {
            $this->verifyTurnstileToken($client);
        }

        return parent::authenticate();
    }

    protected function throwFailureValidationException(): never
    {
        $this->resetTurnstile();

        parent::throwFailureValidationException();
    }

    private function verifyTurnstileToken(TurnstileClientContract $client): void
    {
        if ($this->turnstileToken === '') {
            $this->resetTurnstile();

            throw ValidationException::withMessages([
                'turnstileToken' => 'Please complete the security challenge.',
            ]);
        }

        try {
            $result = $client->verify($this->turnstileToken);
        } catch (TurnstileException) {
            $this->resetTurnstile();

            throw ValidationException::withMessages([
                'turnstileToken' => 'The security challenge could not be verified. Please try again.',
            ]);
        }

        if (! $result->isSuccessful()) {
            $this->resetTurnstile();

            throw ValidationException::withMessages([
                'turnstileToken' => 'The security challenge failed. Please try again.',
            ]);
        }
    }

    private function resetTurnstile(): void
    {
        $this->turnstileToken = '';
        $this->dispatch('turnstile.reset');
    }
}
