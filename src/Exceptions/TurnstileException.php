<?php

declare(strict_types=1);

namespace Sujip\Filament\Turnstile\Exceptions;

use RuntimeException;

/**
 * Base exception for Turnstile credential and verification failures.
 */
abstract class TurnstileException extends RuntimeException {}
