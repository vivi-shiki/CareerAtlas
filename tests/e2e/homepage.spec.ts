import { expect, test } from '@playwright/test'
import { mkdir } from 'node:fs/promises'
import { join } from 'node:path'

const languages = [
    {
        locale: 'en',
        heading: 'Your experience. A story backed by evidence.',
        login: 'Sign in',
        title: 'Your experience, backed by evidence',
    },
    { locale: 'zh-CN', heading: '你的经历， 有据可循。', login: '登录', title: '让经历有据可循' },
    {
        locale: 'ja',
        heading: 'あなたの経験を、 根拠のあるストーリーに。',
        login: 'ログイン',
        title: '根拠で伝える、あなたの経験',
    },
] as const

for (const language of languages) {
    test(`${language.locale}: public homepage, localized images, keyboard tabs and explicit sign-in`, async ({
        page,
    }) => {
        const apiRequests: string[] = []
        page.on('request', (request) => {
            if (new URL(request.url()).pathname.startsWith('/api/')) apiRequests.push(request.url())
        })
        await page.goto('/')
        await page.getByRole('combobox').selectOption(language.locale)
        await expect(page).toHaveURL(/\/$/)
        await expect(page).toHaveTitle(`${language.title} · CareerAtlas`)
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(language.heading)
        await expect(page.getByRole('textbox')).toHaveCount(0)
        const image = page.getByRole('tabpanel').getByRole('img')
        await expect(image).toHaveAttribute('src', `/images/homepage/record-${language.locale}.jpg`)
        const first = page.getByRole('tab').nth(0)
        await first.focus()
        await first.press('ArrowRight')
        await expect(page.getByRole('tab').nth(1)).toBeFocused()
        await expect(page.getByRole('tab').nth(1)).toHaveAttribute('aria-selected', 'true')
        await expect(image).toHaveAttribute(
            'src',
            `/images/homepage/prepare-${language.locale}.jpg`,
        )
        await page.getByRole('tab').nth(1).press('End')
        await expect(image).toHaveAttribute('src', `/images/homepage/share-${language.locale}.jpg`)
        await expect(image).toHaveJSProperty('naturalWidth', 1200)
        await expect(image).toHaveJSProperty('naturalHeight', 560)
        await page.reload()
        await expect(page.getByRole('combobox')).toHaveValue(language.locale)
        expect(apiRequests).toEqual([])
        await page.getByRole('link', { name: language.login, exact: true }).click()
        await expect(page).toHaveURL(/\/login$/)
        await expect(page.locator('input[type="password"]')).toBeVisible()
        await expect(page.getByRole('combobox')).toHaveValue(language.locale)
        await page.getByRole('link', { name: 'CareerAtlas', exact: true }).click()
        await expect(page).toHaveURL(/\/$/)
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(language.heading)
    })

    for (const width of [1440, 768, 390, 320]) {
        test(`${language.locale}: homepage layout and images at ${width}px`, async ({ page }) => {
            const errors: string[] = []
            page.on('pageerror', (error) => errors.push(error.message))
            await page.setViewportSize({ width, height: width === 1440 ? 1152 : 844 })
            await page.goto('/')
            await page.getByRole('combobox').selectOption(language.locale)
            for (const [index, mode] of ['record', 'prepare', 'share'].entries()) {
                await page.getByRole('tab').nth(index).click()
                const image = page.getByRole('tabpanel').getByRole('img')
                await expect(image).toHaveAttribute(
                    'src',
                    `/images/homepage/${mode}-${language.locale}.jpg`,
                )
                await expect(image).toHaveJSProperty('naturalWidth', 1200)
            }
            expect(
                await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
            ).toBe(true)
            const clipped = await page
                .locator('a, button, select, h1, h2, h3')
                .evaluateAll((elements) =>
                    elements
                        .filter((element) => {
                            const rect = element.getBoundingClientRect()
                            return rect.width > 0 && (rect.left < -1 || rect.right > innerWidth + 1)
                        })
                        .map((element) => element.textContent),
                )
            expect(clipped).toEqual([])
            await page.getByRole('tab').nth(0).click()
            await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }))
            const directory = process.env.E2E_HOME_SCREENSHOTS
            if (directory) {
                await mkdir(directory, { recursive: true })
                await page.screenshot({
                    path: join(directory, `home-${language.locale}-${width}.png`),
                    fullPage: true,
                    animations: 'disabled',
                })
            }
            expect(errors).toEqual([])
        })
    }
}

test('Homepage anchors work and private routes retain their authentication guard', async ({
    page,
}) => {
    await page.goto('/')
    await page.getByRole('combobox').selectOption('en')
    await page.getByRole('link', { name: 'Explore the workflow', exact: true }).click()
    await expect(page).toHaveURL(/\/#product$/)
    await expect(page.getByRole('tablist')).toBeInViewport()
    await page.getByRole('link', { name: 'Use cases', exact: true }).click()
    await expect(page).toHaveURL(/\/#use-cases$/)
    await expect(
        page.getByRole('heading', { name: 'One record. Many next steps.', exact: true }),
    ).toBeInViewport()
    await page.route('**/api/auth/me', (route) =>
        route.fulfill({ status: 401, json: { message: 'Unauthenticated.' } }),
    )
    await page.goto('/projects')
    await expect(page).toHaveURL(/\/login$/)
})
