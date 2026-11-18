import { test, expect, projectFixture, experienceFixture, selection, noOverflow } from './helpers'
import { readFileSync } from 'node:fs'

for (const language of ['en', 'zh-CN', 'ja'])
    test(`capture proposal formats dates and record types in ${language}`, async ({
        page,
        owner,
    }) => {
        const catalog = JSON.parse(readFileSync(`resources/js/i18n/${language}.json`, 'utf8'))
        await page.addInitScript(
            (language) => localStorage.setItem('careeratlas:locale', language),
            language,
        )
        await page.setViewportSize({ width: 320, height: 844 })
        const project = await projectFixture(owner)
        const capture = await owner.op('create_capture', {
            project_id: project.id,
            notes: 'Fictional formatting fixture.',
        })
        await page.route('**/api/operations/start_owner_ai', (route) =>
            route.fulfill({
                json: {
                    data: {
                        id: 'formatting-only',
                        state: 'completed',
                        error_code: null,
                        output: {
                            draft: {
                                title: 'Atlas Notes',
                                record_type: 'work_contribution',
                                occurrence: { start: '2026-10', precision: 'month' },
                                skill_suggestions: ['Testing', 'Vue'],
                            },
                            citations: [],
                            questions: [],
                            insufficient_information: false,
                        },
                    },
                },
            }),
        )
        await page.goto(`/capture/${capture.id}`)
        const panel = page.locator('[data-purpose=capture]')
        await panel.getByRole('button', { name: catalog['Draft with AI'], exact: true }).click()
        await expect(panel.locator('dd')).toContainText([
            'Atlas Notes',
            catalog['Work contribution'],
            '2026',
            'Testing · Vue',
        ])
        await expect(panel).not.toContainText('work_contribution')
        await expect(panel).not.toContainText('"precision"')
        await noOverflow(page)
    })

test('owner AI capture is reviewed, applied privately, editable and never auto-confirmed', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const capture = await owner.op('create_capture', {
        project_id: project.id,
        notes: 'I added 12 keyboard tests.',
    })
    await page.goto(`/capture/${capture.id}`)
    const panel = page.locator('[data-purpose=capture]')
    await panel.getByRole('button', { name: 'Draft with AI', exact: true }).click()
    await expect(panel.getByRole('button', { name: 'Use draft', exact: true })).toBeVisible({
        timeout: 25000,
    })
    expect(Object.keys((await owner.op('get_capture', { id: capture.id })).draft)).toHaveLength(0)
    await panel.getByRole('button', { name: 'Use draft', exact: true }).click()
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Keyboard test notes')
    await page.getByLabel('Title', { exact: true }).fill('Reviewed keyboard tests')
    await page.getByRole('button', { name: 'Save notes', exact: true }).click()
    await page.getByRole('button', { name: 'Add as new experience', exact: true }).click()
    await expect(page.getByRole('link', { name: 'Open experience', exact: true })).toBeVisible()
    const saved = await owner.op('get_capture', { id: capture.id }),
        record = await owner.op('get_experience', { id: saved.applied_experience_id })
    expect(record.status).toBe('draft')
    expect(record.provenance).toBe('ai_assisted')
    expect(record.title).toBe('Reviewed keyboard tests')
})

test('preparation AI mappings answers and practice feedback remain explicit owner decisions', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner),
        record = await experienceFixture(owner, project.id)
    const prep = await owner.op('create_preparation', {
        topic: 'Frontend',
        requirements: 'Accessible interfaces',
        experience_ids: [record.id],
    })
    await page.goto(`/preparation/${prep.id}`)
    const mapping = page.locator('[data-purpose=mapping]')
    await mapping.getByRole('button', { name: 'Map requirements to evidence', exact: true }).click()
    await expect(mapping.getByText('Selected evidence describes the contribution.')).toBeVisible({
        timeout: 25000,
    })
    await mapping.getByRole('button', { name: 'Discard proposal', exact: true }).click()
    await page.getByLabel('Question', { exact: true }).fill('What did you contribute?')
    const answer = page.locator('[data-purpose=answer]')
    await answer.getByRole('button', { name: 'Draft an answer', exact: true }).click()
    await expect(answer.getByRole('button', { name: 'Use draft', exact: true })).toBeVisible({
        timeout: 25000,
    })
    await answer.getByRole('button', { name: 'Use draft', exact: true }).click()
    await expect(page.getByLabel('Prepared answer', { exact: true })).toHaveValue(
        /keyboard navigation/,
    )
    expect((await owner.op('get_preparation', { id: prep.id })).answers[0].state).toBe('draft')
    await page.getByLabel('Practice questions', { exact: true }).fill('What did you contribute?')
    await page.getByRole('button', { name: 'Start practice', exact: true }).click()
    await page.getByLabel('Your answer', { exact: true }).fill('I built keyboard navigation.')
    await page.getByRole('button', { name: 'Save attempt', exact: true }).click()
    const feedback = page.locator('[data-purpose=feedback]')
    await feedback.getByRole('button', { name: 'Review this attempt', exact: true }).click()
    await expect(feedback.getByText('Keep the claim within the selected sources.')).toBeVisible({
        timeout: 25000,
    })
    await feedback.getByRole('button', { name: 'Keep feedback', exact: true }).click()
    await expect(page.getByText('Keep the claim within the selected sources.')).toBeVisible()
    expect((await owner.op('get_preparation', { id: prep.id })).practice).toEqual([])
})

test('AI introduction is unconfirmed and export translation requires review before download', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner),
        record = await experienceFixture(owner, project.id)
    const opportunity = await owner.op('create_opportunity', {
        title: 'Fictional opportunity',
        direction: 'Frontend',
        selection: selection(record.id),
    })
    await page.goto(`/opportunities/${opportunity.id}`)
    const intro = page.locator('[data-purpose=introduction]')
    await intro.getByRole('button', { name: 'Draft an introduction', exact: true }).click()
    await expect(intro.getByRole('button', { name: 'Use draft', exact: true })).toBeVisible({
        timeout: 25000,
    })
    await intro.getByRole('button', { name: 'Use draft', exact: true }).click()
    await expect(page.getByLabel('Introduction', { exact: true })).toHaveValue(
        /keyboard navigation/,
    )
    expect((await owner.op('get_opportunity', { id: opportunity.id })).introduction_confirmed).toBe(
        false,
    )
    await page.getByRole('button', { name: 'Review public preview', exact: true }).click()
    await page.getByRole('button', { name: 'Publish this opportunity', exact: true }).click()
    const exported = page.locator('[data-purpose=export]')
    await exported.getByRole('button', { name: 'Translate selected export', exact: true }).click()
    await expect(
        exported.getByRole('button', { name: 'Review translated fields', exact: true }),
    ).toBeVisible({ timeout: 25000 })
    await exported.getByRole('button', { name: 'Review translated fields', exact: true }).click()
    await expect(
        page.getByRole('button', { name: 'Download reviewed translation', exact: true }),
    ).toBeDisabled()
    await page
        .getByLabel('I reviewed the translated fields against the sources.', { exact: true })
        .check()
    const download = page.waitForEvent('download')
    await page.getByRole('button', { name: 'Download reviewed translation', exact: true }).click()
    expect((await download).suggestedFilename()).toBe('portfolio-en.md')
})

test('cancellation and source changes suppress late proposals while keeping manual input', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner),
        record = await experienceFixture(owner, project.id)
    const prep = await owner.op('create_preparation', {
        topic: 'Frontend',
        experience_ids: [record.id],
    })
    await page.goto(`/preparation/${prep.id}`)
    await page.getByLabel('Question', { exact: true }).fill('[test:slow] What did I contribute?')
    const panel = page.locator('[data-purpose=answer]')
    await panel.getByRole('button', { name: 'Draft an answer', exact: true }).click()
    await panel.getByRole('button', { name: 'Cancel drafting', exact: true }).click()
    await expect(panel.getByText('Drafting cancelled. Your saved work is unchanged.')).toBeVisible()
    await panel.getByRole('button', { name: 'Try another draft', exact: true }).click()
    await owner.op('update_experience', {
        id: record.id,
        expected_version: record.version,
        personal_action: 'A changed source.',
    })
    await expect(panel.getByText('Sources changed. Review and select them again.')).toBeVisible({
        timeout: 25000,
    })
    await expect(panel.getByRole('button', { name: 'Use draft', exact: true })).toHaveCount(0)
    await expect(page.getByLabel('Question', { exact: true })).toHaveValue(
        '[test:slow] What did I contribute?',
    )
})

test('disabled and failing owner AI preserve a usable manual workflow', async ({ page, owner }) => {
    const project = await projectFixture(owner),
        record = await experienceFixture(owner, project.id)
    const prep = await owner.op('create_preparation', {
        topic: 'Frontend',
        experience_ids: [record.id],
    })
    await page.route('**/api/operations/owner_ai_status', (route) =>
        route.fulfill({ json: { data: { available: false } } }),
    )
    await page.goto(`/preparation/${prep.id}`)
    await expect(
        page
            .locator('[data-purpose=answer]')
            .getByText('AI drafting is unavailable. You can keep editing manually.'),
    ).toBeVisible()
    await page.unroute('**/api/operations/owner_ai_status')
    await page.reload()
    await page.getByLabel('Question', { exact: true }).fill('[test:failure] What did I contribute?')
    const panel = page.locator('[data-purpose=answer]')
    await panel.getByRole('button', { name: 'Draft an answer', exact: true }).click()
    await expect(
        panel.getByText('Draft unavailable. Your input is preserved; retry when ready.'),
    ).toBeVisible({ timeout: 25000 })
    await page.getByLabel('Prepared answer', { exact: true }).fill('My manually reviewed answer.')
    await page.getByRole('button', { name: 'Save answer draft', exact: true }).click()
    expect((await owner.op('get_preparation', { id: prep.id })).answers[0].answer).toBe(
        'My manually reviewed answer.',
    )
    await noOverflow(page)
})

for (const language of ['zh-CN', 'ja'])
    test(`owner proposals remain usable in ${language} on mobile`, async ({ page, owner }) => {
        const catalog = JSON.parse(readFileSync(`resources/js/i18n/${language}.json`, 'utf8'))
        await page.addInitScript(
            (language) => localStorage.setItem('careeratlas:locale', language),
            language,
        )
        await page.setViewportSize({ width: 390, height: 844 })
        const project = await projectFixture(owner),
            capture = await owner.op('create_capture', {
                project_id: project.id,
                notes: 'I added keyboard navigation tests.',
            })
        await page.goto(`/capture/${capture.id}`)
        const panel = page.locator('[data-purpose=capture]')
        await panel.getByRole('button', { name: catalog['Draft with AI'], exact: true }).click()
        await expect(
            panel.getByRole('button', { name: catalog['Use draft'], exact: true }),
        ).toBeVisible({ timeout: 25000 })
        await panel.getByText(catalog['Sources'], { exact: true }).click()
        await expect(panel.locator('blockquote')).toContainText('keyboard navigation')
        await noOverflow(page)
        await panel.getByRole('button', { name: catalog['Discard proposal'], exact: true }).click()
        await expect(
            panel.getByText(catalog['Drafting cancelled. Your saved work is unchanged.']),
        ).toBeVisible()
    })
