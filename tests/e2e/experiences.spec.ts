import {
    test,
    expect,
    projectFixture,
    experienceFixture,
    waitForOperation,
    databaseObservation,
} from './helpers'

test('EXPERIENCE-01 incomplete drafts persist privately, then confirm after field validation', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    await page.goto(`/projects/${project.id}`)
    await page.getByRole('button', { name: 'Add experience' }).click()
    await page.getByLabel('Title', { exact: true }).fill('A short note')
    const draft = await waitForOperation(page, 'create_experience', () =>
        page.getByRole('button', { name: 'Save draft', exact: true }).click(),
    )
    await expect(page).toHaveURL(new RegExp(`/experiences/${draft.id}$`))
    expect(await owner.op('get_experience', { id: draft.id })).toMatchObject({
        title: 'A short note',
        status: 'draft',
        confirmed_version_id: null,
    })
    await page.reload()
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue('A short note')
    await page.getByRole('button', { name: 'Confirm experience' }).click()
    await expect(page.getByRole('alert')).toContainText(/date|action|contribution|occurrence/i)
    expect((await owner.op('get_experience', { id: draft.id })).status).toBe('draft')
    await page.getByLabel('Precision', { exact: true }).selectOption('month')
    await page.getByLabel('Date', { exact: true }).fill('2026-10')
    await page
        .getByLabel('My contribution', { exact: true })
        .fill('I improved keyboard navigation.')
    await waitForOperation(page, 'confirm_experience', () =>
        page.getByRole('button', { name: 'Confirm experience' }).click(),
    )
    const confirmed = await owner.op('get_experience', { id: draft.id })
    expect(confirmed).toMatchObject({
        status: 'confirmed',
        occurrence: { start: '2026-10', precision: 'month' },
        personal_action: 'I improved keyboard navigation.',
    })
    expect(confirmed.confirmed_version_id).toBeTruthy()
    expect(await owner.op('list_publications')).toHaveLength(0)
    await expect(page.getByText('Confirmed · Private', { exact: true })).toBeVisible()
})

test('EXPERIENCE-02 optional results, validation, remaining work and private notes persist separately', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id, {}, false)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Additional details' }).click()
    await page.getByLabel('Result', { exact: true }).fill('The search is keyboard accessible.')
    await page
        .getByLabel('Validation', { exact: true })
        .fill('Manual keyboard review; no invented metrics.')
    await page.getByLabel('Remaining work', { exact: true }).fill('Review with more users.')
    await page.getByLabel('Private notes', { exact: true }).fill('PRIVATE_SENTINEL_PERSONAL_NOTES')
    await waitForOperation(page, 'update_experience', () =>
        page.getByRole('button', { name: 'Save draft', exact: true }).click(),
    )
    expect(await owner.op('get_experience', { id: experience.id })).toMatchObject({
        result: 'The search is keyboard accessible.',
        validation: 'Manual keyboard review; no invented metrics.',
        remaining_work: 'Review with more users.',
        private_notes: 'PRIVATE_SENTINEL_PERSONAL_NOTES',
    })
})

test('EXPERIENCE-03 stale edits preserve local input and offer explicit reload', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByLabel('My contribution', { exact: true }).fill('My local edit')
    await owner.op('update_experience', {
        id: experience.id,
        expected_version: experience.version,
        personal_action: 'A newer saved action',
    })
    await page.getByRole('button', { name: 'Save draft', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/newer|changed|version/i)
    await expect(page.getByLabel('My contribution', { exact: true })).toHaveValue('My local edit')
    expect((await owner.op('get_experience', { id: experience.id })).personal_action).toBe(
        'A newer saved action',
    )
    await page.getByRole('button', { name: 'Reload saved version' }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Discard changes' }).click()
    await expect(page.getByLabel('My contribution', { exact: true })).toHaveValue(
        'A newer saved action',
    )
})

test('EXPERIENCE-04 confirm twice and edit confirmed content produce controlled revisions', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id, {}, false)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Confirm experience' }).dblclick()
    await expect(page.getByText('Confirmed · Private', { exact: true })).toBeVisible()
    const first = await owner.op('get_experience', { id: experience.id })
    expect((await databaseObservation(owner)).record_versions).toBe(1)
    await page.reload()
    await expect(page.getByLabel('Title', { exact: true })).toHaveValue(experience.title)
    await page.getByLabel('My contribution', { exact: true }).fill('An updated contribution')
    await waitForOperation(page, 'update_experience', () =>
        page.getByRole('button', { name: 'Save draft', exact: true }).click(),
    )
    const edited = await owner.op('get_experience', { id: experience.id })
    expect(edited.status).toBe('draft')
    expect(edited.confirmed_version_id).toBe(first.confirmed_version_id)
    await waitForOperation(page, 'confirm_experience', () =>
        page.getByRole('button', { name: 'Confirm experience' }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).confirmed_version_id).not.toBe(
        first.confirmed_version_id,
    )
    expect((await databaseObservation(owner)).record_versions).toBe(2)
})

test('EXPERIENCE-05 archive, restore and delete are separate owner operations', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await waitForOperation(page, 'archive_experience', () =>
        page.getByRole('button', { name: 'Archive experience' }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).archived_at).not.toBeNull()
    await waitForOperation(page, 'restore_experience', () =>
        page.getByRole('button', { name: 'Restore experience' }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).archived_at).toBeNull()
    await page.getByRole('button', { name: 'Delete experience' }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click()
    expect((await owner.op('list_experiences', { project_id: project.id })).total).toBe(1)
    await page.getByRole('button', { name: 'Delete experience' }).click()
    await waitForOperation(page, 'delete_experience', () =>
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Delete experience', exact: true })
            .click(),
    )
    expect((await owner.op('list_experiences', { project_id: project.id })).total).toBe(0)
})

test('EVIDENCE-01 add a safe supplied link, edit provenance content and delete explicitly', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Add evidence', exact: true }).click()
    const dialog = page.getByRole('dialog', { name: 'Add evidence' })
    await dialog.getByLabel('Evidence type').selectOption('repository')
    await dialog.getByLabel('Label', { exact: true }).fill('Pull request')
    await dialog.getByLabel('URL', { exact: true }).fill('javascript:alert(1)')
    await dialog.getByRole('button', { name: 'Save evidence' }).click()
    await expect(dialog.getByRole('alert')).toContainText(/URL|http|link/i)
    expect(await owner.op('list_evidence', { experience_id: experience.id })).toHaveLength(0)
    await dialog.getByLabel('URL', { exact: true }).fill('https://example.com/pull/24')
    await waitForOperation(page, 'create_evidence', () =>
        dialog.getByRole('button', { name: 'Save evidence' }).click(),
    )
    let evidence = await owner.op('list_evidence', { experience_id: experience.id })
    expect(evidence).toHaveLength(1)
    expect(evidence[0]).toMatchObject({
        kind: 'repository',
        label: 'Pull request',
        provenance: 'supplied_link',
        url: 'https://example.com/pull/24',
    })
    expect((await owner.op('get_experience', { id: experience.id })).status).toBe('draft')
    await page.getByRole('button', { name: 'Edit Pull request' }).click()
    const edit = page.getByRole('dialog', { name: 'Edit evidence' })
    await edit.getByLabel('Label', { exact: true }).fill('Reviewed pull request')
    await waitForOperation(page, 'update_evidence', () =>
        edit.getByRole('button', { name: 'Save evidence' }).click(),
    )
    evidence = await owner.op('list_evidence', { experience_id: experience.id })
    expect(evidence[0].label).toBe('Reviewed pull request')
    await page.getByRole('button', { name: 'Edit Reviewed pull request' }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Delete evidence' }).click()
    await page
        .getByRole('dialog', { name: 'Delete evidence?' })
        .getByRole('button', { name: 'Cancel' })
        .click()
    expect(await owner.op('list_evidence', { experience_id: experience.id })).toHaveLength(1)
    await page
        .getByRole('dialog', { name: 'Edit evidence' })
        .getByRole('button', { name: 'Delete evidence' })
        .click()
    await waitForOperation(page, 'delete_evidence', () =>
        page
            .getByRole('dialog', { name: 'Delete evidence?' })
            .getByRole('button', { name: 'Delete evidence', exact: true })
            .click(),
    )
    expect(await owner.op('list_evidence', { experience_id: experience.id })).toHaveLength(0)
})

test('EVIDENCE-02 owner statements do not become independently verified links', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Add evidence', exact: true }).click()
    const dialog = page.getByRole('dialog', { name: 'Add evidence' })
    await dialog.getByLabel('Evidence type').selectOption('statement')
    await dialog.getByLabel('Label', { exact: true }).fill('Keyboard test notes')
    await dialog.getByLabel('Statement', { exact: true }).fill('I checked tab order manually.')
    await waitForOperation(page, 'create_evidence', () =>
        dialog.getByRole('button', { name: 'Save evidence' }).click(),
    )
    expect((await owner.op('list_evidence', { experience_id: experience.id }))[0]).toMatchObject({
        provenance: 'owner_statement',
        excerpt: 'I checked tab order manually.',
        url: null,
    })
    await expect(page.getByText('Owner statement', { exact: true })).toBeVisible()
})
