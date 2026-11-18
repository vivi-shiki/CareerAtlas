import { test, expect, projectFixture, experienceFixture, selection } from './helpers'
import { mkdir } from 'node:fs/promises'
import { randomUUID } from 'node:crypto'
import { resolve } from 'node:path'
import { readFileSync } from 'node:fs'

// Opt-in: writes the public documentation's fictional screenshots, never production data.
test.skip(process.env.CAPTURE_DOCS !== '1', 'Set CAPTURE_DOCS=1 against a local test database.')

const examples = {
    en: {
        name: 'Alex Chen · Fictional demo',
        role: 'Frontend engineer',
        bio: 'I build accessible interfaces and keep the evidence behind each contribution.',
        project: 'Atlas Notes',
        background: 'A fictional workspace that makes project notes easier to find.',
        title: 'Keyboard-accessible project search',
        action: 'I built keyboard navigation, added focus states, and wrote 12 component tests for the search workflow.',
        result: 'All 12 component tests passed in the demo test suite.',
        notes: 'Next: review the empty state with another keyboard user.',
        question: 'How did you make the search accessible?',
        requirements:
            'Build accessible interfaces, explain engineering decisions, and validate behavior with tests.',
        opportunity: 'Frontend engineering portfolio',
        capture: 'I reviewed the search empty state and added two new keyboard scenarios.',
    },
    'zh-CN': {
        name: '陈晨 · 虚构演示',
        role: '前端工程师',
        bio: '构建易于使用的界面，用具体行动和证据记录每一次贡献。',
        project: 'Atlas Notes',
        background: '一个帮助用户查找项目笔记的虚构工作台。',
        title: '支持键盘操作的项目搜索',
        action: '我实现了键盘导航，补充焦点状态，并为搜索流程编写了 12 个组件测试。',
        result: '演示测试集中的 12 个组件测试全部通过。',
        notes: '下一步：邀请另一位键盘用户检查空状态。',
        question: '你如何让搜索功能支持无障碍操作？',
        requirements: '开发无障碍界面，解释工程决策，并通过测试验证行为。',
        opportunity: '前端工程岗位作品集',
        capture: '我检查了搜索空状态，并增加了两个键盘操作场景。',
    },
    ja: {
        name: '陳 晨 · 架空デモ',
        role: 'フロントエンドエンジニア',
        bio: '使いやすいインターフェースを開発し、具体的な行動と根拠を記録しています。',
        project: 'Atlas Notes',
        background: 'プロジェクトのメモを検索しやすくする架空のワークスペース。',
        title: 'キーボードで操作できるプロジェクト検索',
        action: 'キーボード操作とフォーカス表示を実装し、検索フローに 12 件のコンポーネントテストを追加しました。',
        result: 'デモのテストスイートで 12 件すべてが成功しました。',
        notes: '次は別のキーボード利用者と空の検索結果を確認します。',
        question: '検索機能のアクセシビリティをどう改善しましたか？',
        requirements: 'アクセシブルな UI を開発し、設計判断を説明し、テストで動作を検証する。',
        opportunity: 'フロントエンド職向けポートフォリオ',
        capture: '検索結果が空の状態を確認し、キーボード操作のシナリオを二つ追加しました。',
    },
}

for (const locale of ['en', 'zh-CN', 'ja'] as const) {
    test(`capture fictional documentation in ${locale}`, async ({ page, owner }) => {
        const data = examples[locale]
        const catalog: Record<string, string> = JSON.parse(
            readFileSync(resolve(`resources/js/i18n/${locale}.json`), 'utf8'),
        )
        const t = (key: string) => catalog[key] || key
        await page.addInitScript(
            (language) => localStorage.setItem('careeratlas:locale', language),
            locale,
        )
        await page.setViewportSize({ width: 1440, height: 1000 })
        const output = resolve('docs/assets/screenshots', locale)
        await mkdir(output, { recursive: true })
        const shot = async (name: string) => {
            await page.evaluate(() => document.fonts.ready)
            await page.screenshot({
                path: resolve(output, `${name}.jpg`),
                type: 'jpeg',
                quality: 85,
                animations: 'disabled',
            })
        }
        const visit = async (path: string, name: string) => {
            await page.goto(path)
            await expect(page.locator('main')).toBeVisible()
            await page.waitForLoadState('networkidle')
            await shot(name)
        }
        const profile = await owner.op('get_profile')
        await owner.op('update_profile', {
            expected_version: profile.version,
            display_name: data.name,
            direction: data.role,
            biography: data.bio,
            preferred_language: locale === 'zh-CN' ? 'zh' : locale,
        })
        const project = await projectFixture(owner, {
            title: data.project,
            role: data.role,
            background: data.background,
            team_outcome: data.result,
        })
        let experience = await experienceFixture(
            owner,
            project.id,
            {
                title: data.title,
                personal_action: data.action,
                result: data.result,
                private_notes: data.notes,
            },
            false,
        )
        const skill = await owner.op('create_skill', { name: 'Vue 3' })
        experience = await owner.op('attach_skill', {
            id: experience.id,
            skill_id: skill.id,
            expected_version: experience.version,
        })
        await owner.op('create_evidence', {
            experience_id: experience.id,
            expected_version: experience.version,
            kind: 'repository',
            label: 'Demo · keyboard-search.spec.ts',
            url: 'https://example.com/demo/keyboard-search',
        })
        experience = await owner.op('get_experience', { id: experience.id })
        experience = await owner.op('confirm_experience', {
            id: experience.id,
            expected_version: experience.version,
        })
        const input = selection(experience.id)
        const preview = await owner.op('preview_publication', input)
        const publication = await owner.op('publish_publication', {
            ...input,
            preview_digest: preview.digest,
            idempotency_key: randomUUID(),
        })
        const preparation = await owner.op('create_preparation', {
            topic: data.role,
            requirements: data.requirements,
            target_language: locale === 'zh-CN' ? 'zh' : locale,
            experience_ids: [experience.id],
        })
        await owner.op('create_prepared_answer', {
            preparation_id: preparation.id,
            question: data.question,
            answer: data.action,
        })
        const capture = await owner.op('create_capture', {
            project_id: project.id,
            notes: data.capture,
        })
        const opportunity = await owner.op('create_opportunity', {
            title: data.opportunity,
            direction: data.role,
            requirements: data.requirements,
            target_language: locale === 'zh-CN' ? 'zh' : locale,
            selection: input,
        })
        await visit('/profile', 'profile')
        await visit(`/projects/${project.id}`, 'project')
        await visit(`/experiences/${experience.id}`, 'experience')
        await page.getByRole('button', { name: t('Experience history'), exact: true }).click()
        await expect(page.getByRole('dialog')).toBeVisible()
        await page.waitForLoadState('networkidle')
        await shot('history')
        await page.keyboard.press('Escape')
        await visit(`/skills/${skill.id}`, 'skills')
        await visit(`/capture/${capture.id}`, 'capture')
        if (process.env.CAPTURE_DOCS_AI === '1') {
            const panel = page.locator('[data-purpose="capture"]')
            await panel.getByRole('button', { name: t('Draft with AI'), exact: true }).click()
            await expect(
                panel.getByRole('button', { name: t('Use draft'), exact: true }),
            ).toBeVisible({ timeout: 25_000 })
            await panel.scrollIntoViewIfNeeded()
            await shot('ai-draft')
        }
        await visit(`/preparation/${preparation.id}`, 'preparation')
        await page.getByLabel(t('Practice questions'), { exact: true }).fill(data.question)
        await page.getByRole('button', { name: t('Start practice'), exact: true }).click()
        await page.getByLabel(t('Your answer'), { exact: true }).fill(data.action)
        await page.getByRole('button', { name: t('Save attempt'), exact: true }).click()
        await page.getByTestId('practice-question').scrollIntoViewIfNeeded()
        await shot('practice')
        await visit('/publish', 'publish')
        await visit(`/opportunities/${opportunity.id}`, 'opportunity')
        await page.getByRole('button', { name: t('Review public preview'), exact: true }).click()
        await page.getByTestId('opportunity-preview').scrollIntoViewIfNeeded()
        await shot('preview')
        await visit(`/p/${publication.slug}`, 'portfolio')
        if (process.env.CAPTURE_DOCS_AI === '1') {
            await page.getByLabel(t('Your question'), { exact: true }).fill(data.question)
            await page.getByRole('button', { name: t('Ask question'), exact: true }).click()
            await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 25_000 })
            await page.locator('.agent-answer').scrollIntoViewIfNeeded()
            await shot('qa')
        }
    })
}
