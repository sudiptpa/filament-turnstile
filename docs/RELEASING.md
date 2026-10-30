# Releasing

Run these checks before tagging a release. Automated checks run in CI on every push. The manual checks need a real Cloudflare widget, which CI cannot use.

## Automated (CI)

| Check | Command |
| --- | --- |
| Feature and unit tests | `composer test` |
| Static analysis | `composer stan` |
| Browser tests (Playwright, widget stub) | `npm ci && npx playwright install chromium && npm run test:browser` |

The browser tests start a small Testbench app from `workbench/` on ports 8765 (Turnstile configured) and 8766 (not configured). Cloudflare's script is replaced by `e2e/turnstile-stub.js`, which behaves like the real widget, including the error it throws when a widget that is no longer on the page is reset. Set `PHP_BIN` to pick a PHP binary and `PLAYWRIGHT_CHANNEL=chrome` to use an installed Chrome instead of the downloaded Chromium.

CI runs the browser tests on Filament 5.8.3 and the latest 5.x. Earlier 5.x releases have published security advisories, so Composer refuses to install them by default.

## Manual (real Cloudflare widget)

Use a Filament panel that uses `Sujip\Filament\Turnstile\Pages\Auth\Login` with your own Turnstile site and secret keys (a test pair from Cloudflare's documentation is fine for the first rows).

| # | Step | Expected | Pass |
| --- | --- | --- | --- |
| 1 | Open the login page | Widget loads and completes | |
| 2 | Sign in with a wrong password | Error shown, widget resets, a new check completes | |
| 3 | Sign in with the right password | Dashboard opens | |
| 4 | Wait for the token to expire, then submit | "Please complete the security challenge" or a new token, never a login | |
| 5 | Switch the browser to dark mode | Widget is readable | |
| 6 | MFA account: sign in, enter a wrong code, then the right code | Code step has no widget, no new check, login succeeds | |
| 7 | Browser console during steps 1 to 6 | No uncaught errors | |

## Before you tag

1. Update `CHANGELOG.md` and move the `Unreleased` entries under the new version.
2. Confirm the Filament versions in the README compatibility table match what CI ran.
3. Tag the release.

## Notes for apps that published the views

If an app ran `vendor:publish` for this package, it keeps its own copy of `resources/views/vendor/filament-turnstile/`. The copy does not receive fixes made to the package views. After upgrading, delete the published copy or publish again with `--force`, and re-apply any local changes.
