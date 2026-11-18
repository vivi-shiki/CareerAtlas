import { test, expect, projectFixture, experienceFixture, selection, noOverflow } from './helpers'

test('an empty saved capture can be completed manually after reload', async ({ page, owner }) => {
    const project = await projectFixture(owner)
    const capture = await owner.op('create_capture', {
        project_id: project.id,
        notes: 'Fictional notes without a structured draft.',
    })
    await page.goto(`/capture/${capture.id}`)
    await page.getByLabel('Title', { exact: true }).fill('Manually completed draft')
    await page.getByLabel('Personal action', { exact: true }).fill('I completed the notes.')
    await page.getByRole('button', { name: 'Save notes', exact: true }).click()
    await expect(
        page.getByRole('button', { name: 'Add as new experience', exact: true }),
    ).toBeEnabled()
    await page.reload()
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Manually completed draft')
})

test('interleaved project experiences keep their exact order in preview and public view', async ({
    page,
    owner,
}) => {
    const a = await projectFixture(owner, { title: 'Project A' }),
        b = await projectFixture(owner, { title: 'Project B' })
    const first = await experienceFixture(owner, a.id, {
            personal_action: 'First A contribution.',
        }),
        middle = await experienceFixture(owner, b.id, {
            personal_action: 'Middle B contribution.',
        }),
        last = await experienceFixture(owner, a.id, { personal_action: 'Last A contribution.' })
    const opportunity = await owner.op('create_opportunity', {
        title: 'Ordered portfolio',
        direction: 'Frontend',
        selection: {
            profile_fields: [],
            items: [first, middle, last].map((record) => ({
                experience_id: record.id,
                fields: ['title', 'personal_action'],
                project_fields: ['title'],
            })),
        },
    })
    await page.goto(`/opportunities/${opportunity.id}`)
    await page.getByRole('button', { name: 'Review public preview', exact: true }).click()
    await expect(page.getByTestId('opportunity-preview').locator('.prose-value')).toHaveText([
        'First A contribution.',
        'Middle B contribution.',
        'Last A contribution.',
    ])
    await page.getByRole('button', { name: 'Publish this opportunity', exact: true }).click()
    await expect(page.getByRole('link', { name: 'Open public page', exact: true })).toBeVisible()
    const saved = await owner.op('get_opportunity', { id: opportunity.id })
    await page.goto(`/p/${saved.publication.slug}`)
    await expect(page.locator('.portfolio-content .prose-value')).toHaveText([
        'First A contribution.',
        'Middle B contribution.',
        'Last A contribution.',
    ])
})

test('capture persists, shows duplicates and applies only an unconfirmed draft', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    await experienceFixture(owner, project.id)
    await page.goto('/capture')
    await page.getByRole('combobox', { name: 'Project', exact: true }).selectOption(project.id)
    await page
        .getByLabel('Notes', { exact: true })
        .fill('I improved accessible project search keyboard tests.')
    await page.getByLabel('Title', { exact: true }).fill('Accessible project search follow-up')
    await page.getByLabel('Personal action', { exact: true }).fill('I added keyboard focus tests.')
    await page.getByRole('button', { name: 'Save notes', exact: true }).click()
    await expect(page.getByRole('button', { name: 'Review duplicates', exact: true })).toBeEnabled()
    await page.reload()
    await expect(page.getByLabel('Notes', { exact: true })).toHaveValue(/keyboard tests/)
    await page.getByRole('button', { name: 'Review duplicates', exact: true }).click()
    await expect(page.getByText('Shared project and overlapping text').first()).toBeVisible()
    await page.getByRole('button', { name: 'Add as new experience', exact: true }).click()
    await expect(page.getByRole('link', { name: 'Open experience', exact: true })).toBeVisible()
    const captures = await owner.op('list_captures')
    const record = await owner.op('get_experience', { id: captures[0].applied_experience_id })
    expect(record.status).toBe('draft')
    expect(record.provenance).toBe('manual')
    await noOverflow(page)
})

test('preparation preserves answer versions and practice can retry stop and retain', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    const prep = await owner.op('create_preparation', {
        topic: 'Frontend engineering',
        experience_ids: [experience.id],
    })
    await page.goto(`/preparation/${prep.id}`)
    await page.getByLabel('Question', { exact: true }).fill('What did you contribute?')
    await page
        .getByLabel('Prepared answer', { exact: true })
        .fill('I built keyboard navigation and component tests.')
    await page.getByRole('button', { name: 'Save answer draft', exact: true }).click()
    await page.getByRole('button', { name: 'Confirm answer', exact: true }).click()
    await page.getByRole('button', { name: 'Answer history', exact: true }).click()
    await expect(page.getByRole('dialog')).toContainText('I built keyboard navigation')
    await page.keyboard.press('Escape')
    await page
        .getByLabel('Practice questions', { exact: true })
        .fill('What did you contribute?\nHow did you validate it?')
    await page.getByRole('button', { name: 'Start practice', exact: true }).click()
    await page.getByLabel('Your answer', { exact: true }).fill('I built keyboard navigation.')
    await page.getByRole('button', { name: 'Save attempt', exact: true }).click()
    await page
        .getByLabel('Your answer', { exact: true })
        .fill('I built keyboard navigation and component tests.')
    await page.getByRole('button', { name: 'Save attempt', exact: true }).click()
    await page.getByRole('button', { name: 'Next question', exact: true }).click()
    await expect(page.getByTestId('practice-question')).toHaveText('How did you validate it?')
    await page.getByRole('button', { name: 'Stop practice', exact: true }).click()
    await expect(page.getByLabel('Your answer', { exact: true })).toBeDisabled()
    await page.getByRole('button', { name: 'Keep practice', exact: true }).click()
    const saved = await owner.op('get_preparation', { id: prep.id })
    expect(saved.practice[0].retained).toBe(true)
})

test('opportunity preview export and revocation remain independent', async ({ page, owner }) => {
    const project = await projectFixture(owner),
        experience = await experienceFixture(owner, project.id)
    const opportunity = await owner.op('create_opportunity', {
        title: 'Fictional opportunity',
        direction: 'Frontend',
        requirements: 'PRIVATE_OPPORTUNITY_BRIEF',
        selection: selection(experience.id),
    })
    await page.goto(`/opportunities/${opportunity.id}`)
    await page.getByRole('button', { name: 'Review public preview', exact: true }).click()
    await expect(page.getByTestId('opportunity-preview')).not.toContainText(
        'PRIVATE_OPPORTUNITY_BRIEF',
    )
    await page.getByRole('button', { name: 'Publish this opportunity', exact: true }).click()
    await expect(page.getByRole('link', { name: 'Open public page', exact: true })).toBeVisible()
    const download = page.waitForEvent('download')
    await page.getByRole('button', { name: 'Download Markdown', exact: true }).click()
    expect((await download).suggestedFilename()).toBe('portfolio-en.md')
    await page.getByRole('button', { name: 'Revoke this page', exact: true }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Revoke', exact: true }).click()
    const saved = await owner.op('get_opportunity', { id: opportunity.id })
    expect(saved.publication.state).toBe('revoked')
})

test('source changes block answer confirmation and keep the edited text', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner),
        experience = await experienceFixture(owner, project.id)
    const prep = await owner.op('create_preparation', {
        topic: 'Source freshness',
        experience_ids: [experience.id],
    })
    const answer = await owner.op('create_prepared_answer', {
        preparation_id: prep.id,
        question: 'What did you do?',
        answer: 'My saved draft',
    })
    await owner.op('update_experience', {
        id: experience.id,
        expected_version: experience.version,
        personal_action: 'I changed the source.',
    })
    await page.goto(`/preparation/${prep.id}`)
    await page.getByRole('button', { name: 'Edit answer', exact: true }).click()
    await expect(
        page.getByText('Sources have changed. Review them before confirming.').first(),
    ).toBeVisible()
    await page.getByRole('button', { name: 'Confirm answer', exact: true }).click()
    await expect(page.getByRole('alert')).toBeVisible()
    await expect(page.getByLabel('Prepared answer', { exact: true })).toHaveValue(answer.answer)
})

test('capture keeps unsaved text after network failure and guards navigation', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    await page.goto('/capture')
    await page.getByRole('combobox', { name: 'Project', exact: true }).selectOption(project.id)
    await page.getByLabel('Notes', { exact: true }).fill('Do not lose these fictional notes.')
    await page.route('**/api/operations/create_capture', (route) => route.abort())
    await page.getByRole('button', { name: 'Save notes', exact: true }).click()
    await expect(page.getByRole('alert')).toBeVisible()
    await expect(page.getByLabel('Notes', { exact: true })).toHaveValue(
        'Do not lose these fictional notes.',
    )
    await page.getByRole('link', { name: 'Preparation', exact: true }).click()
    await expect(page.getByRole('dialog')).toBeVisible()
    await page.getByRole('button', { name: 'Keep editing', exact: true }).click()
    await expect(page.getByLabel('Notes', { exact: true })).toBeVisible()
})

test('experience history shows field changes and restores keyboard focus', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner),
        record = await experienceFixture(owner, project.id)
    await owner.op('update_experience', {
        id: record.id,
        expected_version: record.version,
        personal_action: 'I added a fictional focus test.',
    })
    await page.goto(`/experiences/${record.id}`)
    const trigger = page.getByRole('button', { name: 'Experience history', exact: true })
    await trigger.click()
    await expect(page.getByRole('dialog')).toContainText('I added a fictional focus test.')
    await expect(page.getByRole('dialog')).toContainText('Personal action')
    await page.keyboard.press('Escape')
    await expect(trigger).toBeFocused()
})

for (const language of ['en', 'zh-CN', 'ja'])
    for (const width of [1440, 768, 390, 320]) {
        test(`P1 layouts ${language} at ${width}`, async ({ page, owner }) => {
            const errors: string[] = []
            page.on('pageerror', (error) => errors.push(error.message))
            await page.setViewportSize({ width, height: 1000 })
            await page.addInitScript(
                (language) => localStorage.setItem('careeratlas:locale', language),
                language,
            )
            const project = await projectFixture(owner),
                record = await experienceFixture(owner, project.id)
            const prep = await owner.op('create_preparation', {
                topic: 'A long fictional preparation title / 多语言准备 / 多言語の準備',
                experience_ids: [record.id],
            })
            const opportunity = await owner.op('create_opportunity', {
                title: 'A long fictional opportunity / 产品界面 / アクセシビリティ',
                direction: 'Frontend engineering',
                selection: selection(record.id),
            })
            for (const path of [
                '/capture',
                `/preparation/${prep.id}`,
                `/opportunities/${opportunity.id}`,
            ]) {
                await page.goto(path)
                await expect(page.locator('.p1-card').first()).toBeVisible()
                await expect(page.locator('html')).toHaveAttribute('lang', language)
                await noOverflow(page)
                expect(
                    await page
                        .locator('.p1-card button')
                        .evaluateAll((buttons) =>
                            buttons.every((button) => button.getBoundingClientRect().height >= 44),
                        ),
                ).toBe(true)
            }
            expect(errors).toEqual([])
        })
    }
