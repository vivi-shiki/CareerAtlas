import {
    test,
    expect,
    projectFixture,
    experienceFixture,
    publishedFixture,
    waitForOperation,
    databaseObservation,
} from './helpers'

test('REVIEW-01 blank profile name focuses its error without committing', async ({
    page,
    owner,
}) => {
    const before = await owner.op('get_profile')
    await page.goto('/profile')
    await page.getByLabel('Display name', { exact: true }).fill('')
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('alert')).toContainText(/name|required/i)
    await expect(page.getByLabel('Display name', { exact: true })).toBeFocused()
    expect((await owner.op('get_profile')).version).toBe(before.version)
})

test('REVIEW-02 project version conflict retains local fields', async ({ page, owner }) => {
    const project = await projectFixture(owner)
    await page.goto(`/projects/${project.id}/edit`)
    await page.getByLabel('Title', { exact: true }).fill('Unsaved project title')
    await owner.op('update_project', {
        id: project.id,
        expected_version: project.version,
        title: 'Newer project title',
    })
    await page.getByRole('button', { name: 'Save project' }).click()
    await expect(page.getByRole('alert')).toContainText(/version|newer|changed/i)
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue('Unsaved project title')
    expect((await owner.op('get_project', { id: project.id })).title).toBe('Newer project title')
})

test('REVIEW-03 year, day and occurrence ranges retain their selected precision', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id, {}, false)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByLabel('Record type', { exact: true }).selectOption('learning')
    await page.getByLabel('Precision', { exact: true }).selectOption('year')
    await page.getByLabel('Date', { exact: true }).fill('2025')
    await page.getByRole('button', { name: 'Add date range' }).click()
    await page.getByLabel('End precision').selectOption('month')
    await page.getByLabel('End date').fill('2026-10')
    await waitForOperation(page, 'update_experience', () =>
        page.getByRole('button', { name: 'Save draft', exact: true }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).occurrence).toMatchObject({
        start: '2025',
        precision: 'year',
        end: '2026-10',
        end_precision: 'month',
    })
    await page.getByRole('button', { name: 'Remove date range' }).click()
    await page.getByLabel('Precision', { exact: true }).selectOption('day')
    await page.getByLabel('Date', { exact: true }).fill('2026-11-18')
    await waitForOperation(page, 'update_experience', () =>
        page.getByRole('button', { name: 'Save draft', exact: true }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).occurrence).toMatchObject({
        start: '2026-11-18',
        precision: 'day',
    })
})

test('REVIEW-04 selecting contact/team fields includes exactly those fields after review', async ({
    page,
    owner,
}) => {
    const profile = await owner.op('get_profile')
    await owner.op('update_profile', {
        expected_version: profile.version,
        contact_links: [{ label: 'Work samples', url: 'https://example.com/work' }],
    })
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await page.getByRole('checkbox', { name: 'Contact links', exact: true }).check()
    await page.getByRole('checkbox', { name: 'Team outcome', exact: true }).check()
    await page.getByRole('checkbox', { name: 'Bio', exact: true }).uncheck()
    await page.getByRole('checkbox', { name: 'My contribution', exact: true }).uncheck()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await expect(
        page.locator('.preview-frame').getByRole('link', { name: 'Work samples' }),
    ).toHaveAttribute('href', 'https://example.com/work')
    await expect(page.locator('.preview-frame')).toContainText(project.team_outcome)
    await expect(page.locator('.preview-frame')).not.toContainText(experience.personal_action)
    const publication = await waitForOperation(page, 'publish_publication', () =>
        page.getByRole('button', { name: 'Publish snapshot', exact: true }).click(),
    )
    const data = (await (await page.request.get(`/api/public/${publication.slug}`)).json()).data
    expect(data.profile.contact_links).toHaveLength(1)
    expect(data.profile).not.toHaveProperty('biography')
    expect(data.items[0]).not.toHaveProperty('personal_action')
    expect(data.items[0].project.team_outcome).toBe(project.team_outcome)
})

test('REVIEW-05 a lost publish response retries the same operation instead of duplicating publication', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await page.route(
        '**/api/operations/publish_publication',
        async (route) => {
            const committed = await route.fetch()
            expect(committed.status()).toBe(200)
            await route.abort('failed')
        },
        { times: 1 },
    )
    await page.getByRole('button', { name: 'Publish snapshot', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/network|connection|retry/i)
    expect((await databaseObservation(owner)).publications).toBe(1)
    await page.getByRole('button', { name: 'Publish snapshot', exact: true }).click()
    await expect(page.getByText('Published', { exact: true }).first()).toBeVisible()
    expect(await databaseObservation(owner)).toMatchObject({
        publications: 1,
        publication_snapshots: 1,
    })
})

test('REVIEW-06 shared skills count selected experiences and distinct projects only', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const hiddenProject = await projectFixture(owner, { title: 'Hidden skill work' })
    const skill = await owner.op('create_skill', { name: 'TypeScript' })
    const experiences = []
    for (const [index, projectId] of [project.id, project.id, hiddenProject.id].entries()) {
        let experience = await experienceFixture(
            owner,
            projectId,
            { title: `Contribution ${index + 1}` },
            false,
        )
        experience = await owner.op('attach_skill', {
            id: experience.id,
            skill_id: skill.id,
            expected_version: experience.version,
        })
        await owner.op('confirm_experience', {
            id: experience.id,
            expected_version: experience.version,
        })
        experiences.push(experience)
    }
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: 'Include Contribution 1', exact: true }).check()
    await page.getByRole('checkbox', { name: 'Include Contribution 2', exact: true }).check()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    const publication = await waitForOperation(page, 'publish_publication', () =>
        page.getByRole('button', { name: 'Publish snapshot', exact: true }).click(),
    )
    const data = (await (await page.request.get(`/api/public/${publication.slug}`)).json()).data
    expect(data.skills[0]).toMatchObject({ experience_count: 2, project_count: 1 })
    expect(JSON.stringify(data)).not.toContain('Hidden skill work')
    await page.goto(`/p/${publication.slug}`)
    await page.getByRole('button', { name: 'TypeScript', exact: true }).first().click()
    await expect(page.getByRole('dialog')).toContainText('2 experiences')
    await expect(page.getByRole('dialog')).toContainText('1 project')
})

test('REVIEW-07 reopening a source after revocation returns an unavailable dialog', async ({
    page,
    owner,
    browser,
}) => {
    const fixture = await publishedFixture(owner)
    const context = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    const visitor = await context.newPage()
    await visitor.goto(`/p/${fixture.publication.slug}`)
    await visitor.getByRole('button', { name: 'Read project' }).click()
    await expect(visitor.getByRole('dialog')).toContainText(fixture.experience.title)
    await visitor.keyboard.press('Escape')
    await owner.op('revoke_experience_access', {
        id: fixture.experience.id,
        expected_version: fixture.experience.version,
    })
    await visitor.getByRole('button', { name: 'Read project' }).click()
    await expect(visitor.getByRole('dialog')).toContainText('Content unavailable')
    await expect(visitor.getByRole('dialog')).not.toContainText(fixture.experience.title)
    await context.close()
})

test('REVIEW-08 empty publication selection and unconfirmed drafts cannot publish', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    await experienceFixture(owner, project.id, { title: 'Unconfirmed draft' }, false)
    await page.goto('/publish')
    await expect(page.getByText('Confirm an experience before publishing.')).toBeVisible()
    await expect(
        page.getByRole('checkbox', { name: 'Include Unconfirmed draft', exact: true }),
    ).toHaveCount(0)
    expect(await owner.op('list_publications')).toHaveLength(0)
})

test('REVIEW-09 project deletion invalidates published dependent content', async ({
    page,
    owner,
}) => {
    const fixture = await publishedFixture(owner)
    await page.goto(`/projects/${fixture.project.id}`)
    await page.getByRole('button', { name: 'Delete project' }).click()
    await waitForOperation(page, 'delete_project', () =>
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Delete project', exact: true })
            .click(),
    )
    const data = (await (await page.request.get(`/api/public/${fixture.publication.slug}`)).json())
        .data
    expect(data.items).toEqual([])
    expect(data.skills).toEqual([])
})

test('REVIEW-10 an expired CSRF token is refreshed once without losing or duplicating a save', async ({
    page,
    owner,
}) => {
    const profile = await owner.op('get_profile')
    await page.goto('/profile')
    await page.getByLabel('Bio', { exact: true }).fill('Retained after CSRF refresh')
    await page.route(
        '**/api/operations/update_profile',
        (route) =>
            route.fulfill({
                status: 419,
                contentType: 'application/json',
                body: JSON.stringify({ message: 'CSRF token mismatch.' }),
            }),
        { times: 1 },
    )
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('status')).toContainText(/saved/i)
    expect(await owner.op('get_profile')).toMatchObject({
        biography: 'Retained after CSRF refresh',
        version: profile.version + 1,
    })
})

test('REVIEW-11 rate-limited changes preserve input and allow an explicit retry', async ({
    page,
    owner,
}) => {
    const profile = await owner.op('get_profile')
    await page.goto('/profile')
    await page.getByLabel('Bio', { exact: true }).fill('Retained after rate limit')
    await page.route(
        '**/api/operations/update_profile',
        (route) =>
            route.fulfill({
                status: 429,
                contentType: 'application/json',
                headers: { 'Retry-After': '1' },
                body: JSON.stringify({ message: 'Too many requests. Try again shortly.' }),
            }),
        { times: 1 },
    )
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('alert')).toContainText(/requests|retry|shortly/i)
    expect((await owner.op('get_profile')).version).toBe(profile.version)
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue('Retained after rate limit')
    await waitForOperation(page, 'update_profile', () =>
        page.getByRole('button', { name: 'Save profile' }).click(),
    )
    expect((await owner.op('get_profile')).biography).toBe('Retained after rate limit')
})
