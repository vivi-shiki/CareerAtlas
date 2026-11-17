import { test, expect, publishedFixture, noOverflow, waitForOperation } from './helpers'
import en from '../../resources/js/i18n/en.json' with { type: 'json' }
import zh from '../../resources/js/i18n/zh-CN.json' with { type: 'json' }
import ja from '../../resources/js/i18n/ja.json' with { type: 'json' }
import { mkdir } from 'node:fs/promises'
import { join } from 'node:path'
import type { Page } from '@playwright/test'

const catalogs: Record<string, Record<string, string>> = { en, 'zh-CN': zh, ja }
async function layouts(page: Page, language: string, screen: string) {
    for (const width of [1487, 768, 390, 320]) {
        await page.setViewportSize({ width, height: 1058 })
        await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }))
        await noOverflow(page)
        const clipped = await page
            .locator('button:visible, select:visible, input:visible, textarea:visible')
            .evaluateAll((elements) =>
                elements
                    .filter((element) => {
                        const r = element.getBoundingClientRect()
                        return r.width > 2 && (r.left < -1 || r.right > innerWidth + 1)
                    })
                    .map((element) => element.outerHTML.slice(0, 160)),
            )
        expect(clipped, `${language} ${screen} ${width}`).toEqual([])
        if (process.env.E2E_I18N_SCREENSHOTS) {
            await mkdir(process.env.E2E_I18N_SCREENSHOTS, { recursive: true })
            await page.screenshot({
                path: join(process.env.E2E_I18N_SCREENSHOTS, `${language}-${screen}-${width}.png`),
                fullPage: !(await page.getByRole('dialog').isVisible()),
            })
        }
    }
    await page.setViewportSize({ width: 1487, height: 1058 })
}
for (const language of ['en', 'zh-CN', 'ja']) {
    const tr = (key: string) => catalogs[language]![key] || key
    test(`I18N ${language}: login errors, persistence, native language labels and unknown routes`, async ({
        page,
    }) => {
        await page.goto('/login')
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await expect(page.locator('html')).toHaveAttribute('lang', language)
        await expect(page).toHaveTitle(`${tr('Sign in')} · CareerAtlas`)
        await page.getByRole('button', { name: tr('Sign in'), exact: true }).click()
        await expect(page.getByRole('alert')).toContainText(
            language === 'zh-CN'
                ? '请填写邮箱。'
                : language === 'ja'
                  ? 'メールアドレスは必須です。'
                  : 'The email field is required.',
        )
        await page.getByLabel(tr('Email'), { exact: true }).fill('fictional@example.test')
        await layouts(page, language, 'login-error')
        await page.getByRole('combobox', { name: tr('Interface language') }).selectOption('en')
        await expect(page.getByRole('alert')).toContainText('The email field is required.')
        await expect(page.getByLabel('Email', { exact: true })).toHaveValue(
            'fictional@example.test',
        )
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await page.reload()
        await expect(page.locator('html')).toHaveAttribute('lang', language)
        await page.goto('/nonexistent-page')
        await expect(page.getByRole('heading', { name: tr('Content unavailable') })).toBeVisible()
        await expect(page.getByRole('combobox', { name: tr('Interface language') })).toBeVisible()
        await layouts(page, language, 'unavailable')
    })

    test(`I18N ${language}: complete authoring, preview, publication, source and revocation`, async ({
        page,
        owner,
    }) => {
        const errors: string[] = []
        page.on('pageerror', (error) => errors.push(error.message))
        await page.goto('/profile')
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await page.getByLabel(tr('Display name'), { exact: true }).fill('林 Atlas — Fictional')
        await page
            .getByLabel(tr('Bio'), { exact: true })
            .fill('中文・日本語・English\nOriginal portfolio text.')
        // Language is presentation state: dirty input and confirmation state survive switching.
        await page.getByRole('combobox', { name: tr('Interface language') }).selectOption('en')
        await expect(page.getByLabel('Bio', { exact: true })).toHaveValue(
            '中文・日本語・English\nOriginal portfolio text.',
        )
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await layouts(page, language, 'profile-dirty')
        await waitForOperation(page, 'update_profile', () =>
            page.getByRole('button', { name: tr('Save profile'), exact: true }).click(),
        )
        await expect(page.getByRole('status')).toContainText(tr('Profile saved.'))
        expect((await owner.op('get_profile')).preferred_language).toBe('en')
        await page.setViewportSize({ width: 320, height: 1058 })
        await page.getByRole('button', { name: tr('Toggle navigation') }).click()
        await page
            .locator('.sidebar')
            .getByRole('link', { name: tr('Projects'), exact: true })
            .click()
        await expect(
            page.getByRole('heading', { name: tr('Start with a project'), exact: true }),
        ).toBeVisible()
        await expect(page.getByRole('button', { name: tr('Toggle navigation') })).toHaveAttribute(
            'aria-expanded',
            'false',
        )
        await layouts(page, language, 'projects-empty')
        await page.goto('/projects/new')
        await page.getByLabel(tr('Title'), { exact: true }).fill('多语言作品集 · Portfolio')
        await page.getByLabel(tr('Role'), { exact: true }).fill('開発者 / Developer')
        await page
            .getByLabel(tr('Background'), { exact: true })
            .fill('LongProjectName'.repeat(30) + '\n项目背景・背景・Context')
        await layouts(page, language, 'project-form')
        const project = await waitForOperation(page, 'create_project', () =>
            page.getByRole('button', { name: tr('Create project'), exact: true }).click(),
        )
        await expect(
            page.getByRole('heading', { name: '多语言作品集 · Portfolio', exact: true }),
        ).toBeVisible()
        await page.getByRole('button', { name: tr('Add experience'), exact: true }).click()
        await page.getByRole('button', { name: tr('Confirm experience'), exact: true }).click()
        await expect(page.getByRole('alert')).toContainText(tr('Add an occurrence date.'))
        await page.getByLabel(tr('Title'), { exact: true }).fill('多言語検索 / Multilingual search')
        await page.getByLabel(tr('Date'), { exact: true }).fill('2026-10')
        await page
            .getByLabel(tr('My contribution'), { exact: true })
            .fill('我实现了搜索。検索とキーボード操作を実装。I built keyboard navigation.')
        await waitForOperation(page, 'create_experience', () =>
            page.getByRole('button', { name: tr('Save draft'), exact: true }).click(),
        )
        await expect(page).toHaveURL(/\/experiences\//)
        await page.getByRole('button', { name: tr('Add skill'), exact: true }).click()
        await page.getByRole('dialog').getByLabel(tr('Skill name'), { exact: true }).fill('Vue 3')
        await page.getByRole('button', { name: tr('Connect skill'), exact: true }).click()
        await expect(page.getByRole('dialog')).toBeHidden()
        await page.getByRole('button', { name: tr('Add evidence'), exact: true }).click()
        await page.getByLabel(tr('Evidence type'), { exact: true }).selectOption('statement')
        await page.getByLabel(tr('Label'), { exact: true }).fill('本人の説明 · 说明')
        await page
            .getByLabel(tr('Statement'), { exact: true })
            .fill('Fictional keyboard walkthrough. 键盘测试。')
        await layouts(page, language, 'evidence-dialog')
        await page.getByRole('button', { name: tr('Save evidence'), exact: true }).click()
        await expect(page.getByRole('dialog')).toBeHidden()
        await layouts(page, language, 'experience')
        await waitForOperation(page, 'confirm_experience', () =>
            page.getByRole('button', { name: tr('Confirm experience'), exact: true }).click(),
        )
        await page.goto('/skills')
        await expect(
            page.getByText(
                language === 'en'
                    ? '1 project · 1 experience'
                    : language === 'ja'
                      ? 'プロジェクト 1 件 · 経験 1 件'
                      : '1 个项目 · 1 条经历',
                { exact: true },
            ),
        ).toBeVisible()
        await page.getByRole('link', { name: 'Vue 3', exact: true }).click()
        await page.getByLabel(tr('New alias'), { exact: true }).fill('ビュー / 视图')
        await page.getByRole('button', { name: tr('Add alias'), exact: true }).click()
        await expect(page.getByText('ビュー / 视图', { exact: true })).toBeVisible()
        await page.goto('/publish')
        await page
            .getByRole('checkbox', {
                name: tr('Include {title}').replace('{title}', '多言語検索 / Multilingual search'),
            })
            .check()
        await layouts(page, language, 'publication-selection')
        await page.getByRole('button', { name: tr('Review selected content'), exact: true }).click()
        await expect(page.locator('.visitor-preview')).toContainText('我实现了搜索。')
        await expect(page.locator('.visitor-preview')).toContainText(
            language === 'en' ? 'October 2026' : '2026年10月',
        )
        await layouts(page, language, 'publication-preview')
        const publication = await waitForOperation(page, 'publish_publication', () =>
            page.getByRole('button', { name: tr('Publish snapshot'), exact: true }).click(),
        )
        await page.getByRole('link', { name: tr('Open public portfolio'), exact: true }).click()
        await expect(
            page.getByRole('heading', { name: '林 Atlas — Fictional', exact: true }),
        ).toBeVisible()
        await expect(page.getByLabel(tr('Your question'), { exact: true })).toBeVisible()
        await layouts(page, language, 'public')
        await page.getByRole('button', { name: tr('Read project'), exact: true }).click()
        await expect(page.getByRole('dialog')).toContainText('多言語検索 / Multilingual search')
        await layouts(page, language, 'source-dialog')
        await page.keyboard.press('Escape')
        await expect(page.getByRole('dialog')).toBeHidden()
        await page.goto('/publish')
        await page.getByRole('button', { name: tr('Revoke publication'), exact: true }).click()
        await expect(page.getByRole('dialog')).toContainText(
            tr(
                'Future online access to this page, its sources and its questions will stop. Copies already viewed cannot be recalled.',
            ),
        )
        await page
            .getByRole('dialog')
            .getByRole('button', { name: tr('Revoke publication'), exact: true })
            .click()
        await expect(page.getByRole('status')).toContainText(tr('Publication revoked.'))
        await page.goto(`/p/${publication.slug}`)
        await expect(page.getByRole('heading', { name: tr('Content unavailable') })).toBeVisible()
        expect((await owner.op('get_project', { id: project.id })).title).toBe(
            '多语言作品集 · Portfolio',
        )
        expect(errors).toEqual([])
    })

    test(`I18N ${language}: agent suggestions, history, citations, failure and cancellation`, async ({
        page,
        owner,
    }) => {
        const { publication } = await publishedFixture(owner)
        await page.goto(`/p/${publication.slug}`)
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await page
            .getByRole('button', { name: tr('What did this person build?'), exact: true })
            .click()
        await expect(page.getByLabel(tr('Your question'), { exact: true })).toHaveValue(
            tr('What did this person build?'),
        )
        await page.getByRole('button', { name: tr('Ask question'), exact: true }).click()
        await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 25000 })
        const answer = await page.locator('.agent-answer').innerText()
        await page.getByRole('combobox', { name: tr('Interface language') }).selectOption('en')
        await expect(page.locator('.agent-answer')).toHaveText(answer)
        await page.getByRole('combobox', { name: 'Interface language' }).selectOption(language)
        await page.locator('.agent-citations button').first().click()
        await expect(page.getByRole('dialog')).toContainText('I built keyboard navigation')
        await page.keyboard.press('Escape')
        await page.getByLabel(tr('Your question'), { exact: true }).fill('[test:failure]')
        await page.getByRole('button', { name: tr('Ask question'), exact: true }).click()
        await expect(
            page.getByText(tr('An answer is unavailable right now. Your question is preserved.'), {
                exact: true,
            }),
        ).toBeVisible({ timeout: 25000 })
        await layouts(page, language, 'agent-failure')
        await page.getByLabel(tr('Your question'), { exact: true }).fill('[test:slow]')
        await page.getByRole('button', { name: tr('Ask question'), exact: true }).click()
        await page.getByRole('button', { name: tr('Cancel answer'), exact: true }).click()
        await expect(
            page.getByText(tr('Answer cancelled. You can ask another question.'), { exact: true }),
        ).toBeVisible()
        await page.reload()
        await expect(page.locator('html')).toHaveAttribute('lang', language)
        await expect(page.locator('.agent-answer')).toHaveText(answer)
    })
}

test('I18N browser language variants, fallback and disabled storage', async ({
    browser,
    baseURL,
}) => {
    for (const [browserLanguage, expected] of [
        ['ja-JP', 'ja'],
        ['zh-TW', 'zh-CN'],
        ['fr-FR', 'en'],
    ]) {
        const context = await browser.newContext({ baseURL, locale: browserLanguage })
        await context.addInitScript(() => {
            Object.defineProperty(window, 'localStorage', {
                get() {
                    throw new DOMException('Storage disabled', 'SecurityError')
                },
            })
        })
        const page = await context.newPage()
        const errors: string[] = []
        page.on('pageerror', (error) => errors.push(error.message))
        await page.goto('/login')
        await expect(page.locator('html')).toHaveAttribute('lang', expected!)
        await page.getByRole('combobox').selectOption('ja')
        await expect(page.getByRole('button', { name: 'ログイン', exact: true })).toBeVisible()
        expect(errors).toEqual([])
        await context.close()
    }
})
