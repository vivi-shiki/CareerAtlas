import {
    test,
    expect,
    projectFixture,
    experienceFixture,
    publishedFixture,
    waitForOperation,
    post,
    databaseObservation,
} from './helpers'

test('PUBLISH-01 explicit preview equals the public snapshot and excludes private/unselected content', async ({
    page,
    owner,
    browser,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    const privateProject = await projectFixture(owner, { title: 'PRIVATE_PROJECT_SENTINEL' })
    await experienceFixture(
        owner,
        privateProject.id,
        { title: 'PRIVATE_EXPERIENCE_SENTINEL' },
        false,
    )
    expect(await databaseObservation(owner)).toMatchObject({
        projects: 2,
        experiences: 2,
        record_versions: 1,
        publications: 0,
    })
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await expect(
        page.getByRole('checkbox', { name: 'Contact links', exact: true }),
    ).not.toBeChecked()
    await expect(
        page.getByRole('checkbox', { name: 'Team outcome', exact: true }),
    ).not.toBeChecked()
    await expect(page.getByRole('checkbox', { name: 'Private notes', exact: true })).toHaveCount(0)
    const preview = await waitForOperation(page, 'preview_publication', () =>
        page.getByRole('button', { name: 'Review selected content' }).click(),
    )
    await expect(page.getByRole('heading', { name: 'Visitor preview' })).toBeVisible()
    await expect(page.locator('.preview-frame')).toContainText(experience.personal_action)
    await expect(page.locator('.preview-frame')).not.toContainText('PRIVATE_')
    const publication = await waitForOperation(page, 'publish_publication', () =>
        page.getByRole('button', { name: 'Publish snapshot', exact: true }).click(),
    )
    expect(await owner.op('list_publications')).toHaveLength(1)
    expect(await databaseObservation(owner)).toMatchObject({
        publications: 1,
        publication_snapshots: 1,
    })
    const visitor = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    const response = await visitor.request.get(`/api/public/${publication.slug}`)
    const publicData = (await response.json()).data
    expect({
        profile: publicData.profile,
        items: publicData.items,
        skills: publicData.skills,
    }).toEqual(preview.portfolio)
    expect(publicData.items).toHaveLength(1)
    expect(publicData.items[0].id).toBe(experience.id)
    expect(JSON.stringify(publicData)).not.toContain('PRIVATE_')
    expect(publicData.profile).not.toHaveProperty('contact_links')
    expect(publicData.items[0].project).not.toHaveProperty('team_outcome')
    const publicPage = await visitor.newPage()
    await publicPage.goto(`/p/${publication.slug}`)
    await expect(publicPage.getByText(experience.personal_action, { exact: true })).toBeVisible()
    await expect(publicPage.getByText('Alex Morgan', { exact: true })).toHaveCount(0)
    await visitor.close()
})

test('PUBLISH-02 source edit keeps the old snapshot; reviewed republication advances it', async ({
    page,
    owner,
    browser,
}) => {
    const fixture = await publishedFixture(owner)
    await page.goto(`/experiences/${fixture.experience.id}`)
    await page
        .getByLabel('My contribution', { exact: true })
        .fill('I added accessible filter shortcuts.')
    await waitForOperation(page, 'confirm_experience', () =>
        page.getByRole('button', { name: 'Confirm experience' }).click(),
    )
    const publicBefore = (
        await (await page.request.get(`/api/public/${fixture.publication.slug}`)).json()
    ).data
    expect(publicBefore.items[0].personal_action).toBe(fixture.experience.personal_action)
    await page.goto('/publish')
    await expect(page.getByText('Unpublished changes', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Review changes', exact: true }).click()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await expect(page.locator('.preview-frame')).toContainText(
        'I added accessible filter shortcuts.',
    )
    await waitForOperation(page, 'republish_publication', () =>
        page.getByRole('button', { name: 'Publish snapshot', exact: true }).click(),
    )
    const publicAfter = (
        await (await page.request.get(`/api/public/${fixture.publication.slug}`)).json()
    ).data
    expect(publicAfter.items[0].personal_action).toBe('I added accessible filter shortcuts.')
    expect(publicAfter.version).toBe(publicBefore.version + 1)
})

test('PUBLISH-03 changing data after preview requires a fresh review', async ({ page, owner }) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await expect(page.getByRole('heading', { name: 'Visitor preview' })).toBeVisible()
    const profile = await owner.op('get_profile')
    await owner.op('update_profile', {
        expected_version: profile.version,
        biography: 'Changed while reviewing',
    })
    await page.getByRole('button', { name: 'Publish snapshot', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/preview|review|changed/i)
    expect(await owner.op('list_publications')).toHaveLength(0)
})

test('PUBLISH-04 double click produces one publication and the button is locked while pending', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await page.getByRole('button', { name: 'Publish snapshot', exact: true }).dblclick()
    await expect(page.getByText('Published', { exact: true }).first()).toBeVisible()
    expect(await owner.op('list_publications')).toHaveLength(1)
    expect(await databaseObservation(owner)).toMatchObject({
        publications: 1,
        publication_snapshots: 1,
    })
})

test('PUBLISH-05 revoked publication becomes unavailable to an existing anonymous tab', async ({
    page,
    owner,
    browser,
}) => {
    const { publication, experience, skill, project } = await publishedFixture(owner)
    const visitor = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    const publicPage = await visitor.newPage()
    await publicPage.goto(`/p/${publication.slug}`)
    await expect(publicPage.getByText(experience.personal_action, { exact: true })).toBeVisible()
    await page.goto('/publish')
    await page.getByRole('button', { name: 'Revoke publication' }).click()
    await page
        .getByRole('dialog', { name: 'Revoke publication?' })
        .getByRole('button', { name: 'Cancel' })
        .click()
    expect((await visitor.request.get(`/api/public/${publication.slug}`)).status()).toBe(200)
    await page.getByRole('button', { name: 'Revoke publication' }).click()
    await waitForOperation(page, 'revoke_publication', () =>
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Revoke publication', exact: true })
            .click(),
    )
    expect((await owner.op('get_publication', { id: publication.id })).state).toBe('revoked')
    for (const suffix of [
        '',
        `/projects/${project.id}`,
        `/skills/${skill.id}`,
        `/experiences/${experience.id}`,
    ])
        expect(
            (await visitor.request.get(`/api/public/${publication.slug}${suffix}`)).status(),
        ).toBe(404)
    await publicPage.reload()
    await expect(publicPage.getByRole('heading', { name: 'Content unavailable' })).toBeVisible()
    await expect(publicPage.getByText(experience.personal_action, { exact: true })).toHaveCount(0)
    await visitor.close()
})

test('PUBLISH-06 withdrawing source access clears public records and counts without deleting the private record', async ({
    page,
    owner,
}) => {
    const { publication, experience } = await publishedFixture(owner)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Withdraw public access' }).click()
    await waitForOperation(page, 'revoke_experience_access', () =>
        page
            .getByRole('dialog', { name: 'Withdraw public access?' })
            .getByRole('button', { name: 'Withdraw access' })
            .click(),
    )
    const result = (await (await page.request.get(`/api/public/${publication.slug}`)).json()).data
    expect(result.items).toEqual([])
    expect(result.skills).toEqual([])
    expect((await owner.op('get_experience', { id: experience.id })).title).toBe(experience.title)
})

test('PUBLISH-07 mobile selection and preview are sequential and keep field choices', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/publish')
    await page.getByRole('checkbox', { name: `Include ${experience.title}`, exact: true }).check()
    await page.getByRole('checkbox', { name: 'Team outcome', exact: true }).check()
    await page.getByRole('button', { name: 'Review selected content' }).click()
    await expect(page.getByRole('heading', { name: 'Visitor preview' })).toBeVisible()
    await expect(page.getByRole('checkbox', { name: 'Team outcome', exact: true })).toBeHidden()
    await expect(page.locator('.preview-frame')).toContainText(project.team_outcome)
    for (const name of ['Back to selection', 'Publish snapshot']) {
        const bounds = await page.getByRole('button', { name, exact: true }).boundingBox()
        expect(bounds?.height).toBeGreaterThanOrEqual(44)
        expect(bounds?.height).toBeLessThanOrEqual(88)
    }
    await page.getByRole('button', { name: 'Back to selection' }).click()
    await expect(page.getByRole('checkbox', { name: 'Team outcome', exact: true })).toBeChecked()
})
