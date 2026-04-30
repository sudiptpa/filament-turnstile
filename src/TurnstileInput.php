<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile;

use Filament\Forms\Components\Field;
use Sujip\Filament\Turnstile\Contracts\TurnstileClientContract;
use Sujip\Filament\Turnstile\Rules\TurnstileRule;

final class TurnstileInput extends Field
{
    protected string $view = 'filament-turnstile::turnstile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
        $this->hiddenLabel();

        $this->rule(static fn (): TurnstileRule => new TurnstileRule(
            app(TurnstileClientContract::class),
        ));
    }

    public function getSiteKey(): ?string
    {
        return app(TurnstileClientContract::class)->siteKey();
    }

    public function isConfigured(): bool
    {
        return app(TurnstileClientContract::class)->isConfigured();
    }
}
