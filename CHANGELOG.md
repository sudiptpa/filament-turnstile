# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Comprehensive test suite (19 tests) covering client, rule, and value objects
- PHPStan level 8 static analysis coverage
- GitHub Actions CI matrix testing PHP 8.2–8.5 with Laravel 12–13
- Support for Orchestra Testbench 10.x (Laravel 12) and 11.x (Laravel 13)
- Professional documentation with usage examples for Filament forms, form requests, plain Laravel controllers, and custom resolvers
- Test keys for local development (always pass, always block, forces interactive)
- View customization via `php artisan vendor:publish`

### Supported Versions

- **PHP**: 8.2, 8.3, 8.4, 8.5
- **Laravel**: 12.x, 13.x
- **Filament**: v5.0+ (optional)

[1.0.0]: https://github.com/sudiptpa/filament-turnstile/releases/tag/v1.0.0
