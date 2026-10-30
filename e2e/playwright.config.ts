import { defineConfig } from '@playwright/test';

const channel = process.env.PLAYWRIGHT_CHANNEL || undefined;
const php = process.env.PHP_BIN || 'php';

export default defineConfig({
    testDir: '.',
    testMatch: '*.spec.ts',
    // Both servers keep state in their own database and cache, so tests run one at a time.
    workers: 1,
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    outputDir: '../test-results',
    reporter: process.env.CI ? [['list'], ['html', { open: 'never', outputFolder: '../playwright-report' }]] : 'list',
    use: { channel, trace: 'retain-on-failure', screenshot: 'only-on-failure' },
    webServer: [
        {
            command: `PHP_BIN=${php} bin/browser-server 8765`,
            cwd: '..',
            url: 'http://127.0.0.1:8765/admin/login',
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
        {
            command: `PHP_BIN=${php} bin/browser-server 8766 unconfigured`,
            cwd: '..',
            url: 'http://127.0.0.1:8766/admin/login',
            reuseExistingServer: !process.env.CI,
            timeout: 120_000,
        },
    ],
});
