<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Exceptions;

/**
 * Thrown when the Cloudflare verification request fails or returns an unusable response.
 */
final class TurnstileVerificationFailed extends TurnstileException {}
