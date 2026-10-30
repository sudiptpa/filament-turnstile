# Filament Turnstile

[![CI](https://github.com/sudiptpa/filament-turnstile/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/filament-turnstile/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/sudiptpa/filament-turnstile.svg)](https://packagist.org/packages/sudiptpa/filament-turnstile)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Cloudflare Turnstile for Filament 5 and plain Laravel. Add `TurnstileInput` to a Filament form, protect the admin login with one line, or use the validation rule and Blade component anywhere in Laravel.

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.2 to 8.5 |
| Laravel | 12 and 13 |
| Filament | 5.8.3 or later (optional) |

Filament is only needed for `TurnstileInput` and the `Login` page. The validation rule and Blade component work without it. Filament 5.x releases before 5.8.3 have published security advisories, so use 5.8.3 or later.

## Installation

```bash
composer require sudiptpa/filament-turnstile
php artisan vendor:publish --tag=filament-turnstile-config
```

The service provider is registered by package auto-discovery. Add your keys to `.env`. You can create them in the Cloudflare dashboard under Turnstile.

```dotenv
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

## Usage

### Filament form field

Add `TurnstileInput` like any other field. The widget renders in the form, the token is validated on the server when the form is submitted, and nothing is saved to your model.

```php
use Sujip\Filament\Turnstile\TurnstileInput;

public function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('email')->email()->required(),
        Textarea::make('message')->required(),
        TurnstileInput::make('turnstile_token'),
    ]);
}
```

The field is hidden when no keys are configured, so local development works without them.

### Filament admin login

Use the bundled login page in your panel provider:

```php
use Sujip\Filament\Turnstile\Pages\Auth\Login;

public function panel(Panel $panel): Panel
{
    return $panel
        ->login(Login::class)
        // ...
}
```

The widget is shown when keys are configured and skipped when they are not.

The page works with Filament's multi-factor authentication (email, authenticator app or custom providers) without extra setup:

- The check runs once, on the email and password step.
- Entering the code, retrying a wrong code and resending a code need no new check. Filament handles the challenge and its limits.
- Changing the email or password during the code step restarts it and needs a new check.

Panels without multi-factor authentication behave as before.

### Blade component

```html
<form method="POST" action="/contact">
    @csrf
    <input type="email" name="email" />

    <x-turnstile />

    <button type="submit">Send</button>
</form>
```

Cloudflare adds a hidden `cf-turnstile-response` field when the challenge is completed. The component takes `theme` (`light`, `dark` or `auto`, the default), `size` (`normal`, the default, or `compact`) and any HTML attributes:

```html
<x-turnstile theme="dark" size="compact" />
```

### Validation rule

Validate the `cf-turnstile-response` field with `TurnstileRule`, in a form request or with `$request->validate()` in a controller:

```php
use Sujip\Filament\Turnstile\Rules\TurnstileRule;

public function rules(): array
{
    return [
        'email'                 => ['required', 'email'],
        'cf-turnstile-response' => ['required', new TurnstileRule()],
    ];
}
```

The rule gets its HTTP client from the service container. In tests you can pass one: `new TurnstileRule(app(TurnstileClientContract::class))`.

## Custom key resolver

Keys are read from `config/filament-turnstile.php` by default. To load them from somewhere else, such as a database, tenant settings or a secrets manager, implement `TurnstileKeyResolverContract` and bind it:

```php
use Sujip\Filament\Turnstile\Contracts\TurnstileKeyResolverContract;
use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

final class YourKeyResolver implements TurnstileKeyResolverContract
{
    public function credentials(): ?TurnstileCredentials
    {
        $siteKey   = /* load from your source */;
        $secretKey = /* load from your source */;

        if (blank($siteKey) || blank($secretKey)) {
            return null;
        }

        return new TurnstileCredentials($siteKey, $secretKey);
    }
}
```

```php
// AppServiceProvider::register()
$this->app->bind(TurnstileKeyResolverContract::class, YourKeyResolver::class);
```

Return `null` when keys are not available. The widget is hidden and validation is skipped. `TurnstileInput`, the `Login` page, `TurnstileRule` and `<x-turnstile />` all use this contract.

## Configuration

| Option | Env variable | Default |
|---|---|---|
| `site_key` | `TURNSTILE_SITE_KEY` | none |
| `secret_key` | `TURNSTILE_SECRET_KEY` | none |
| `verify_url` | `TURNSTILE_VERIFY_URL` | `https://challenges.cloudflare.com/turnstile/v0/siteverify` |
| `connect_timeout` | none | 5 seconds |
| `timeout` | none | 10 seconds |

## Testing

Cloudflare publishes test keys that work on any domain, including `localhost`:

| Purpose | Site key | Secret key |
|---|---|---|
| Always passes | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Always fails | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |
| Forces interactive | `3x00000000000000000000FF` | `1x0000000000000000000000000000000AA` |
| Token already spent | `1x00000000000000000000AA` | `3x0000000000000000000000000000000AA` |

Set them in `.env.testing` or `phpunit.xml`:

```xml
<php>
    <env name="TURNSTILE_SITE_KEY"   value="1x00000000000000000000AA"/>
    <env name="TURNSTILE_SECRET_KEY" value="1x0000000000000000000000000000000AA"/>
</php>
```

## Customising the views

```bash
php artisan vendor:publish --tag=filament-turnstile-views
```

The views are published to `resources/views/vendor/filament-turnstile/`. A published copy does not get later fixes to the package views. After upgrading, delete it or publish again with `--force`.

## Development

```bash
composer install
composer test        # PHPUnit
composer stan        # PHPStan level 8
composer coverage    # Coverage report (needs Xdebug)
```

The checks to run before a release are in [docs/RELEASING.md](docs/RELEASING.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT, see [LICENSE](LICENSE).
