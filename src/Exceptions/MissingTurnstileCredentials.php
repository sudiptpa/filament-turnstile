<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Exceptions;

/**
 * Thrown when a verification is attempted without configured site and secret keys.
 */
final class MissingTurnstileCredentials extends TurnstileException
{
    public function __construct()
    {
        parent::__construct('Cloudflare Turnstile credentials are not configured.');
    }
}
