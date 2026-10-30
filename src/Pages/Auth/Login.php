<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Facades\Filament;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Validation\ValidationException;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Exceptions\TurnstileException;
use Throwable;

/**
 * Filament admin login page that adds a Turnstile challenge before authentication.
 */
class Login extends FilamentLogin
{
    public string $turnstileToken = '';

    private bool $turnstileResetDispatched = false;

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

        if (! $client->isConfigured()) {
            return parent::authenticate();
        }

        if ($this->hasPendingMultiFactorChallenge()) {
            if ($this->pendingChallengeMatchesSubmittedAccount()) {
                // Code submission: the token was consumed on the credentials stage.
                return parent::authenticate();
            }

            // Credentials changed mid-challenge. Restart the credentials stage.
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->throwTurnstileError('Please complete the security challenge.');
        }

        $this->turnstileResetDispatched = false;

        $this->verifyTurnstileToken($client);

        // Token is single-use and consumed from here on.
        try {
            $response = parent::authenticate();
        } catch (Throwable $exception) {
            $this->resetTurnstileOnce();

            throw $exception;
        }

        if ($response === null && ! $this->hasPendingMultiFactorChallenge()) {
            // Parent rate-limited the attempt; a fresh token is needed.
            $this->resetTurnstileOnce();
        } else {
            // Login succeeded or MFA started. Drop the spent token without
            // touching the challenge; the widget re-renders on restart.
            $this->turnstileToken = '';
        }

        return $response;
    }

    protected function throwFailureValidationException(): never
    {
        $this->resetTurnstileOnce();

        parent::throwFailureValidationException();
    }

    private function verifyTurnstileToken(TurnstileClientContract $client): void
    {
        if ($this->turnstileToken === '') {
            $this->throwTurnstileError('Please complete the security challenge.');
        }

        try {
            $result = $client->verify($this->turnstileToken);
        } catch (TurnstileException) {
            $this->throwTurnstileError('The security challenge could not be verified. Please try again.');
        }

        if (! $result->isSuccessful()) {
            $this->throwTurnstileError('The security challenge failed. Please try again.');
        }
    }

    private function resetTurnstile(): void
    {
        $this->turnstileToken = '';
        $this->turnstileResetDispatched = true;
        $this->dispatch('turnstile.reset');
    }

    private function resetTurnstileOnce(): void
    {
        if ($this->turnstileResetDispatched) {
            $this->turnstileToken = '';

            return;
        }

        $this->resetTurnstile();
    }

    private function throwTurnstileError(string $message): never
    {
        $this->resetTurnstileOnce();

        throw ValidationException::withMessages([
            'turnstileToken' => $message,
        ]);
    }

    /**
     * Filament's locked, encrypted identifier is set by the parent only after
     * credentials and panel access were validated (Filament v5).
     */
    private function hasPendingMultiFactorChallenge(): bool
    {
        return filled($this->userUndertakingMultiFactorAuthentication);
    }

    /**
     * True only when the submitted credentials belong to the account the
     * pending challenge was issued for. Filament still rechecks everything
     * itself; this only decides whether a new CAPTCHA token is needed.
     */
    private function pendingChallengeMatchesSubmittedAccount(): bool
    {
        try {
            $data = is_array($this->data) ? $this->data : [];

            if (! is_string($data['email'] ?? null) || ! is_string($data['password'] ?? null)) {
                return false;
            }

            $pending = $this->userUndertakingMultiFactorAuthentication;
            $guard = Filament::auth();

            if (! is_string($pending) || ! $guard instanceof SessionGuard) {
                return false;
            }

            $credentials = $this->getCredentialsFromFormData($data);
            $provider = $guard->getProvider();
            $user = $provider->retrieveByCredentials($credentials);

            if ($user === null || ! $provider->validateCredentials($user, $credentials)) {
                return false;
            }

            // Older Filament releases store the bare identifier. Newer ones
            // store an array that also holds a user-scoped key, which the
            // parent verifies before completing the login.
            $payload = decrypt($pending);
            $identifier = is_array($payload) ? ($payload['identifier'] ?? null) : $payload;

            return $identifier !== null && $identifier === $user->getAuthIdentifier();
        } catch (Throwable) {
            return false;
        }
    }
}
