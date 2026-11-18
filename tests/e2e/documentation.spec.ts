import { test, expect } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { resolve, join } from 'node:path'
import { tmpdir } from 'node:os'

const languages = [
    {
        locale: 'en',
        documentation: 'Documentation',
        search: 'Search',
        query: 'practice',
        chapter: 'Interview preparation',
        home: 'Product home',
    },
    {
        locale: 'zh-CN',
        documentation: '使用文档',
        search: '搜索文档',
        query: '练习',
        chapter: '面试准备',
        home: '产品首页',
    },
    {
        locale: 'ja',
        documentation: 'ドキュメント',
        search: 'ドキュメントを検索',
        query: '練習',
        chapter: '面接準備',
        home: '製品ホーム',
    },
]

for (const language of languages) {
    test(`${language.locale}: homepage to docs, local search, language switch and return`, async ({
        page,
    }) => {
        const errors: string[] = []
        page.on('pageerror', (error) => errors.push(error.message))
        page.on('console', (message) => {
            if (message.type() === 'error') errors.push(message.text())
        })
        // Match production's policy; the development PHP server does not add nginx headers.
        await page.route('**/docs/**', async (route) => {
            if (!route.request().isNavigationRequest()) return route.continue()
            const response = await route.fetch()
            await route.fulfill({
                response,
                headers: {
                    ...response.headers(),
                    'content-security-policy':
                        "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'",
                },
            })
        })
        await page.goto('/')
        await page.getByRole('combobox').selectOption(language.locale)
        await page.getByRole('link', { name: language.documentation, exact: true }).click()
        await expect(page).toHaveURL(new RegExp(`/docs/${language.locale}/index.html$`))
        await expect(page).toHaveTitle(/CareerAtlas/)
        await expect(page.locator('.vp-doc h1')).toBeVisible()
        await expect(page.locator('html')).toHaveAttribute('lang', language.locale)
        await page
            .getByRole('button', { name: language.search, exact: language.locale !== 'en' })
            .first()
            .click()
        await page.locator('#localsearch-input').fill(language.query)
        await expect(page.locator('.VPLocalSearchBox .result').first()).toBeVisible()
        await page.keyboard.press('Escape')
        await page
            .locator('.VPSidebar')
            .getByRole('link', { name: language.chapter, exact: true })
            .click()
        await expect(page).toHaveURL(new RegExp(`/docs/${language.locale}/preparation.html$`))
        await expect(page.locator('.vp-doc h1')).toHaveText(language.chapter)
        const currentImages = await page
            .locator('.vp-doc img')
            .evaluateAll((images) =>
                images.every(
                    (image) =>
                        (image as HTMLImageElement).complete &&
                        (image as HTMLImageElement).naturalWidth > 0,
                ),
            )
        expect(currentImages).toBe(true)
        await page.locator('.docs-language').selectOption(language.locale === 'en' ? 'ja' : 'en')
        await expect(page).toHaveURL(
            new RegExp(`/docs/${language.locale === 'en' ? 'ja' : 'en'}/preparation.html$`),
        )
        await page.goto(`/docs/${language.locale}/index.html`)
        await page.screenshot({
            path: join(tmpdir(), `careeratlas-docs-${language.locale}-desktop.png`),
            fullPage: false,
            animations: 'disabled',
        })
        await page.locator('.VPSidebar .product-home').click()
        await expect(page).toHaveURL(/\/$/)
        await expect(page.getByRole('combobox')).toHaveValue(language.locale)
        expect(errors).toEqual([])
    })

    test(`${language.locale}: mobile documentation navigation at 390 and 320 pixels`, async ({
        page,
    }) => {
        for (const width of [390, 320]) {
            await page.setViewportSize({ width, height: 844 })
            await page.goto(`/docs/${language.locale}/index.html`)
            await expect(page.locator('.vp-doc h1')).toBeVisible()
            expect(
                await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
            ).toBe(true)
            await page.locator('.VPLocalNav .menu').click()
            await page
                .locator('.VPSidebar')
                .getByRole('link', { name: language.chapter, exact: true })
                .click()
            await expect(page).toHaveURL(/\/preparation.html$/)
            await expect(page.locator('.vp-doc h1')).toBeVisible()
            expect(
                await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth),
            ).toBe(true)
            await expect(page.locator('.VPSidebar')).not.toHaveClass(/open/)
            if (width === 390)
                await page.screenshot({
                    path: join(tmpdir(), `careeratlas-docs-${language.locale}-mobile.png`),
                    fullPage: false,
                    animations: 'disabled',
                })
        }
    })
}

test('language switching preserves a chapter while its content is still loading', async ({
    page,
}) => {
    await page.goto('/docs/en/index.html')
    // Initialize the client before deliberately holding the next chapter's lazy bundle.
    await page.locator('.VPNavBarSearch button').click()
    await expect(page.locator('#localsearch-input')).toBeVisible()
    await page.keyboard.press('Escape')
    let release: () => void = () => {}
    const gate = new Promise<void>((resolve) => {
        release = resolve
    })
    let requested: () => void = () => {}
    const pending = new Promise<void>((resolve) => {
        requested = resolve
    })
    await page.route(/\/assets\/en_preparation\.md\..*\.js$/, async (route) => {
        requested()
        await gate
        await route.continue()
    })
    try {
        await page
            .locator('.VPSidebar')
            .getByRole('link', { name: 'Interview preparation', exact: true })
            .click()
        await pending
        await expect(page).toHaveURL(/\/docs\/en\/preparation.html$/)
        await page.locator('.docs-language').selectOption('ja')
        await expect(page).toHaveURL(/\/docs\/ja\/preparation.html$/)
        await expect(page.locator('.vp-doc h1')).toHaveText('面接準備')
    } finally {
        release()
    }
})

test('every static guide, screenshot and local asset resolves without signing in', async ({
    request,
}) => {
    const features = JSON.parse(readFileSync(resolve('docs/feature-map.json'), 'utf8'))
    const pages = [
        'index',
        'quickstart',
        'records',
        'capture',
        'preparation',
        'publishing',
        'opportunities',
        'privacy-and-ai',
        'architecture',
        'development',
        'mcp',
        'troubleshooting',
        'roadmap',
    ]
    expect(features).toHaveLength(39)
    const resources = new Set<string>()
    for (const language of languages) {
        for (const chapter of pages) {
            const url = `/docs/${language.locale}/${chapter}.html`
            const response = await request.get(url)
            expect(response.status(), url).toBe(200)
            const html = await response.text()
            expect(html).toContain(`<html lang="${language.locale}"`)
            for (const match of html.matchAll(/(?:src|href)="(\/docs\/[^"#]+)"/g))
                resources.add(match[1])
        }
    }
    for (const url of resources) {
        const response = await request.get(url)
        expect(response.status(), url).toBe(200)
    }
})
