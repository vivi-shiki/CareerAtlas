import { defineConfig, devices } from '@playwright/test'
import { tmpdir } from 'node:os'
import { join } from 'node:path'

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8001'
if (!['127.0.0.1', 'localhost'].includes(new URL(baseURL).hostname)) {
    throw new Error('The synthetic acceptance suite must target a local test server.')
}

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 180_000,
    expect: { timeout: 8_000 },
    outputDir: join(tmpdir(), 'careeratlas-playwright-results'),
    reporter: [
        ['list'],
        ['json', { outputFile: join(tmpdir(), 'careeratlas-playwright-results.json') }],
    ],
    use: {
        baseURL,
        ...devices['Desktop Chrome'],
        locale: 'en-US',
        viewport: { width: 1487, height: 1058 },
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
})
