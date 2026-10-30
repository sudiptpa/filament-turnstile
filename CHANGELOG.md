# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-10-30

### Fixed

- `Login` page is now Filament multi-factor authentication aware. Turnstile is verified on the credentials submission only; submitting an MFA code (email, authenticator app or custom provider) delegates straight to Filament and no longer re-verifies the single-use token.
- Changing the email/password while an MFA challenge is pending clears the challenge and requires a fresh Turnstile token, so a verified token cannot be used to trigger codes for another account.
- The widget and token are reset after wrong credentials, schema errors and rate-limited credentials attempts, without disturbing an active MFA challenge. Reset events are no longer dispatched twice.
- The widget views now remove their reset listener and widget when the element is removed, and ignore a reset for a widget that no longer exists. This stops the `Nothing to reset found for provided container` browser error when the login form is replaced by the MFA step and back.
- A wrong password during a pending MFA challenge now requires a fresh Turnstile token instead of reusing the verified one.

### Changed

- `filament/filament` `^5.0` added as a dev dependency; `Login` page is now covered by tests and static analysis.
- Filament 5.8.3 is the lowest version listed as supported. Earlier 5.x releases have published security advisories.
- Added release notes in `docs/RELEASING.md`, a compatibility table in the README, and a test for the widget views.

## [1.0.0] - 2026-04-30

### Added

- Initial stable release of Cloudflare Turnstile integration for Filament v5 and plain Laravel
- `TurnstileInput` form field component for Filament forms with automatic server-side validation
- Bundled `Login` page for protecting Filament admin authentication
- `TurnstileRule` validation rule with zero-argument constructor (auto-resolves from service container)
- `<x-turnstile />` Blade component for standalone use in plain Laravel applications
- `TurnstileKeyResolverContract` for custom credential resolution (per-tenant, multi-database, secrets manager)
- `TurnstileClientContract` for dependency injection and testability
- HTTP client with configurable timeouts, 3-retry logic, and 100ms backoff
- Configuration file with Cloudflare Turnstile endpoint customization
- Test suite (19 tests) covering the client, the rule and the value objects
- PHPStan level 8 static analysis coverage
- GitHub Actions CI matrix testing PHP 8.2 to 8.5 with Laravel 12 and 13
- Support for Orchestra Testbench 10.x (Laravel 12) and 11.x (Laravel 13)
- Professional documentation with usage examples for Filament forms, form requests, plain Laravel controllers, and custom resolvers
- Test keys for local development (always pass, always block, forces interactive)
- View customization via `php artisan vendor:publish`

### Supported Versions

- **PHP**: 8.2, 8.3, 8.4, 8.5
- **Laravel**: 12.x, 13.x
- **Filament**: v5.0+ (optional)

[1.0.0]: https://github.com/sudiptpa/filament-turnstile/releases/tag/v1.0.0
