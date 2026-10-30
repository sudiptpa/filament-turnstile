# Releasing

## Checks that run in CI

Run the same checks locally before tagging:

```bash
composer test
composer stan
```

CI runs them on PHP 8.2 to 8.5 with Laravel 12 and 13, using the latest Filament 5.x.

Filament 5.x releases before 5.8.3 have published security advisories, and Composer will not install them by default. Treat 5.8.3 as the lowest supported version.

## Check with the real widget

CI cannot load the real Cloudflare widget, so check these by hand in a Filament panel that uses `Sujip\Filament\Turnstile\Pages\Auth\Login`. Use your own Turnstile keys. Cloudflare's test keys always pass, so they cannot show an expired token.

- [ ] The widget loads and completes on the login page.
- [ ] A wrong password shows the error, resets the widget, and a new check completes.
- [ ] The right password signs in.
- [ ] Let the token expire, then submit. The form must not sign you in.
- [ ] The widget is readable in dark mode.
- [ ] With an MFA account, enter a wrong code and then the right one. The code step shows no widget and asks for no new check.
- [ ] The browser console shows no uncaught errors during the steps above.

## Tagging

1. Move the `Unreleased` entries in `CHANGELOG.md` under the new version.
2. Check that the Requirements table in the README matches what CI ran.
3. Tag the release.

Apps that published the package views keep their own copy and need to republish after an upgrade. See the README.
