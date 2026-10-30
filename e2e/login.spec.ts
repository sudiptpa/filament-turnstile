import { expect, test, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';

const CONFIGURED = 'http://127.0.0.1:8765';
const UNCONFIGURED = 'http://127.0.0.1:8766';
const PASSWORD = 'password';
const CODE = '123456';
const stub = readFileSync(join(__dirname, 'turnstile-stub.js'), 'utf8');

let pageErrors: string[] = [];

test.beforeEach(async ({ page, request }) => {
    pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));

    // Real Cloudflare is never contacted; the stub follows the same API.
    await page.route('https://challenges.cloudflare.com/turnstile/v0/api.js', (route) =>
        route.fulfill({ contentType: 'application/javascript', body: stub }),
    );

    for (const base of [CONFIGURED, UNCONFIGURED]) {
        await request.post(`${base}/__e2e/reset`);
    }
});

test.afterEach(() => {
    expect(pageErrors, 'uncaught browser errors').toEqual([]);
});

async function verifyCalls(page: Page, base = CONFIGURED): Promise<number> {
    const response = await page.request.get(`${base}/__e2e/state`, { headers: { Accept: 'application/json' } });

    return (await response.json()).verifyCalls;
}

const wire = (page: Page) => ({
    token: () => page.evaluate(() => (window as any).Livewire.first().turnstileToken as string),
    set: (key: string, value: string) =>
        page.evaluate(([k, v]) => (window as any).Livewire.first().$set(k, v), [key, value]),
    call: (method: string) => page.evaluate((m) => (window as any).Livewire.first().call(m), method),
});

const token = (page: Page) => wire(page).token();
const stubStats = (page: Page) => page.evaluate(() => (window as any).__turnstileStub.stats);
const liveWidgets = (page: Page) => page.evaluate(() => (window as any).__turnstileStub.liveWidgets() as number);

async function waitForToken(page: Page) {
    await expect.poll(() => token(page)).toMatch(/^stub-/);
}

async function openLogin(page: Page, base = CONFIGURED) {
    await page.goto(`${base}/admin/login`);
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
}

async function submitCredentials(page: Page, email: string, password = PASSWORD) {
    await page.getByRole('textbox', { name: 'Email address' }).fill(email);
    await page.getByRole('textbox', { name: 'Password' }).fill(password);
    await page.getByRole('button', { name: 'Sign in' }).click();
}

// Filament's code input differs by version: some submit on the last digit, some
// wait for the button, and some use a single field. Type the code, then press
// the button only if nothing was submitted by itself.
async function enterCode(page: Page, code: string) {
    const boxes = page.locator('input[inputmode="numeric"]');
    const count = await boxes.count();
    const autoSubmit = page.waitForRequest(
        (request) => request.method() === 'POST' && (request.postData() ?? '').includes('authenticate'),
        { timeout: 2000 },
    );
    autoSubmit.catch(() => undefined);

    if (count === 1) {
        await boxes.first().fill(code);
        await boxes.first().blur();
    } else {
        for (let i = 0; i < count; i++) {
            await boxes.nth(i).fill('');
        }

        await boxes.first().click();
        await page.keyboard.type(code);
    }

    const submitted = await autoSubmit.then(() => true).catch(() => false);

    if (!submitted) {
        await page.getByRole('button', { name: /confirm sign in/i }).click();
    }
}

const challengeHeading = (page: Page) => page.getByRole('heading', { name: 'Verify your identity' });

test.describe('Turnstile configured, no MFA', () => {
    test('signs in with one verification', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);

        await submitCredentials(page, 'plain@example.test');

        await expect(page).toHaveURL(/\/admin$/);
        expect(await verifyCalls(page)).toBe(1);
    });

    test('wrong password resets the widget and allows a retry', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);

        await submitCredentials(page, 'plain@example.test', 'wrong-password');

        await expect(page.getByText('These credentials do not match our records.')).toBeVisible();
        await expect.poll(async () => (await stubStats(page)).resets).toBe(1);
        await waitForToken(page);

        await page.getByRole('textbox', { name: 'Password' }).fill(PASSWORD);
        await page.getByRole('button', { name: 'Sign in' }).click();

        await expect(page).toHaveURL(/\/admin$/);
        expect(await verifyCalls(page)).toBe(2);
    });

    test('missing token blocks sign in', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);
        await wire(page).set('turnstileToken', '');

        await submitCredentials(page, 'plain@example.test');

        await expect(page.getByText('Please complete the security challenge.')).toBeVisible();
        await expect(page).toHaveURL(/\/admin\/login/);
    });

    test('rejected token blocks sign in', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);
        await wire(page).set('turnstileToken', 'bad-token');

        await submitCredentials(page, 'plain@example.test');

        await expect(page.getByText('The security challenge failed. Please try again.')).toBeVisible();
        await expect(page).toHaveURL(/\/admin\/login/);
    });
});

test.describe('Turnstile configured, MFA account', () => {
    test('code step needs no new token', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);

        await submitCredentials(page, 'mfa@example.test');

        await expect(challengeHeading(page)).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Password' })).toHaveCount(0);
        expect(await verifyCalls(page)).toBe(1);

        await enterCode(page, '000000');
        await expect(page.getByText('The code you entered is invalid.')).toBeVisible();
        await expect(challengeHeading(page)).toBeVisible();
        expect(await verifyCalls(page)).toBe(1);

        await enterCode(page, CODE);

        await expect(page).toHaveURL(/\/admin$/);
        expect(await verifyCalls(page)).toBe(1);
    });

    test('switching account mid-challenge needs a fresh token', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);
        await submitCredentials(page, 'mfa@example.test');
        await expect(challengeHeading(page)).toBeVisible();

        await wire(page).set('data.email', 'other-mfa@example.test');
        await wire(page).set('data.multiFactor.email_code.code', CODE);
        await wire(page).call('authenticate');

        await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
        await expect(page.getByText('Please complete the security challenge.')).toBeVisible();
        expect(await verifyCalls(page)).toBe(1);

        // The credentials form is back with exactly one working widget.
        await expect.poll(() => liveWidgets(page)).toBe(1);
        await waitForToken(page);
        expect((await stubStats(page)).resetErrors).toBe(0);

        await submitCredentials(page, 'other-mfa@example.test');
        await expect(challengeHeading(page)).toBeVisible();
        expect(await verifyCalls(page)).toBe(2);
    });

    test('wrong password mid-challenge needs a fresh token', async ({ page }) => {
        await openLogin(page);
        await waitForToken(page);
        await submitCredentials(page, 'mfa@example.test');
        await expect(challengeHeading(page)).toBeVisible();

        await wire(page).set('data.password', 'guess-1');
        await wire(page).call('authenticate');

        await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
        await expect(page.getByText('Please complete the security challenge.')).toBeVisible();
        expect(await verifyCalls(page)).toBe(1);
        expect((await stubStats(page)).resetErrors).toBe(0);
    });
});

test.describe('Turnstile not configured', () => {
    test('no widget, normal account signs in', async ({ page }) => {
        await openLogin(page, UNCONFIGURED);
        await expect(page.locator('script[src*="challenges.cloudflare.com"]')).toHaveCount(0);

        await submitCredentials(page, 'plain@example.test');

        await expect(page).toHaveURL(/\/admin$/);
        expect(await verifyCalls(page, UNCONFIGURED)).toBe(0);
    });

    test('MFA account reaches the code step and signs in', async ({ page }) => {
        await openLogin(page, UNCONFIGURED);

        await submitCredentials(page, 'mfa@example.test');
        await expect(challengeHeading(page)).toBeVisible();
        await enterCode(page, CODE);

        await expect(page).toHaveURL(/\/admin$/);
        expect(await verifyCalls(page, UNCONFIGURED)).toBe(0);
    });
});
