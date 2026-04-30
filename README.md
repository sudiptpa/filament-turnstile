# Filament Turnstile

[![CI](https://github.com/sudiptpa/filament-turnstile/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/filament-turnstile/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/sudiptpa/filament-turnstile.svg)](https://packagist.org/packages/sudiptpa/filament-turnstile)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A Cloudflare Turnstile integration built for **Filament v5** and plain Laravel. Drop `TurnstileInput` into any Filament form schema, protect the admin login with one line, or use the standalone validation rule and Blade component in any Laravel controller, form request, or view — no CAPTCHA, no image puzzles, just a silent, privacy-respecting bot challenge.

---

## Supported Versions

| Dependency | Version |
|---|---|
| PHP | 8.2, 8.3, 8.4 |
| Laravel | ^12.0, ^13.0 |
| Filament | ^5.0 (optional) |

Filament is only required when using `TurnstileInput` or the bundled `Login` page. The validation rule and Blade component work in any Laravel application.

---

## Installation

```bash
composer require sudiptpa/filament-turnstile
```

The service provider is registered automatically via Laravel package auto-discovery.

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-turnstile-config
```

Add your Cloudflare Turnstile keys to `.env`:

```dotenv
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

> Register your domain and get free API keys at [dash.cloudflare.com](https://dash.cloudflare.com) → **Turnstile**.

---

## Usage

### 1. Any Filament Form

Add `TurnstileInput` to any Filament schema like any other field. The widget renders automatically, the token is validated server-side on submit, and nothing is persisted to your model.

```php
use Sujip\Filament\Turnstile\TurnstileInput;

public function form(Schema $schema): Schema
{
    return $schema->components([
        TextInput::make('name')->required(),
        TextInput::make('email')->email()->required(),
        Textarea::make('message')->required(),
        TurnstileInput::make('turnstile_token'),
    ]);
}
```

The field automatically hides itself when credentials are not configured, so local development without keys works out of the box.

---

### 2. Filament Admin Login

Swap the default Filament login page with the bundled one in your panel provider:

```php
use Sujip\Filament\Turnstile\Pages\Auth\Login;

public function panel(Panel $panel): Panel
{
    return $panel
        ->login(Login::class)
        // ...
}
```

The login page renders the Turnstile widget when credentials are present and skips it when they are not — no conditional logic required in your application.

---

### 3. Plain Laravel — Blade Component

Use the `<x-turnstile />` component in any Blade template. The component accepts optional `theme`, `size`, and any additional HTML attributes.

```html
<form method="POST" action="/contact">
    @csrf
    <input type="text" name="name" />
    <input type="email" name="email" />

    <x-turnstile />

    <button type="submit">Send</button>
</form>
```

Cloudflare automatically injects a hidden `cf-turnstile-response` field on challenge completion.

Available props:

| Prop | Default | Options |
|---|---|---|
| `theme` | `auto` | `light`, `dark`, `auto` |
| `size` | `normal` | `normal`, `compact` |

```html
<x-turnstile theme="dark" size="compact" />
```

---

### 4. Plain Laravel — Form Requests

```php
use Sujip\Filament\Turnstile\Rules\TurnstileRule;

class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string'],
            'email'                 => ['required', 'email'],
            'cf-turnstile-response' => ['required', new TurnstileRule()],
        ];
    }
}
```

---

### 5. Plain Laravel — Controllers

```php
use Sujip\Filament\Turnstile\Rules\TurnstileRule;

public function store(Request $request): RedirectResponse
{
    $request->validate([
        'name'                  => ['required', 'string'],
        'email'                 => ['required', 'email'],
        'cf-turnstile-response' => ['required', new TurnstileRule()],
    ]);

    // handle the form ...
}
```

The `TurnstileRule` resolves its HTTP client from the service container automatically. You can also pass a client explicitly when needed (e.g. in tests):

```php
new TurnstileRule(app(TurnstileClientContract::class))
```

---

## Custom Key Resolver

By default, credentials are read from `config/filament-turnstile.php`. If your application resolves keys differently — for example, from a database, per-tenant settings, or a secrets manager — implement `TurnstileKeyResolverContract` and bind it in your `AppServiceProvider`:

```php
use Sujip\Filament\Turnstile\Contracts\TurnstileKeyResolverContract;
use Sujip\Filament\Turnstile\Support\TurnstileCredentials;

final class YourKeyResolver implements TurnstileKeyResolverContract
{
    public function credentials(): ?TurnstileCredentials
    {
        $siteKey   = /* resolve from your source */;
        $secretKey = /* resolve from your source */;

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

Return `null` to indicate that credentials are unavailable — the widget will be suppressed and validation skipped automatically. `TurnstileInput`, the `Login` page, `TurnstileRule`, and `<x-turnstile />` all resolve credentials through this single contract.

---

## Configuration Reference

```php
// config/filament-turnstile.php
return [
    'site_key'        => env('TURNSTILE_SITE_KEY'),
    'secret_key'      => env('TURNSTILE_SECRET_KEY'),
    'verify_url'      => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
    'connect_timeout' => 5,
    'timeout'         => 10,
];
```

---

## Testing Locally

Cloudflare provides official test keys that work on any domain, including `localhost`, without registration:

| Purpose | Site key | Secret key |
|---|---|---|
| Always passes | `1x00000000000000000000AA` | `1x0000000000000000000000000000000AA` |
| Always blocks | `2x00000000000000000000AB` | `2x0000000000000000000000000000000AA` |
| Forces interactive | `3x00000000000000000000FF` | `3x0000000000000000000000000000000AA` |

Add these to your `.env.testing` or declare them in `phpunit.xml`:

```xml
<php>
    <env name="TURNSTILE_SITE_KEY"   value="1x00000000000000000000AA"/>
    <env name="TURNSTILE_SECRET_KEY" value="1x0000000000000000000000000000000AA"/>
</php>
```

---

## Customising the Views

Publish the package views to override the widget HTML:

```bash
php artisan vendor:publish --tag=filament-turnstile-views
```

Views are published to `resources/views/vendor/filament-turnstile/`.

---

## Running Package Tests

```bash
composer install
composer test        # PHPUnit
composer stan        # PHPStan level 8
composer coverage    # Coverage report (requires Xdebug)
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT — see [LICENSE](LICENSE).
