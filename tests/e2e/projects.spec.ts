import { test, expect, projectFixture, experienceFixture, waitForOperation } from './helpers'

test('PROJECT-01 create a learning project with year-only ongoing time and edit it', async ({
    page,
    owner,
}) => {
    await page.goto('/projects')
    await page.getByRole('button', { name: 'Create your first project' }).click()
    await page.getByLabel('Title', { exact: true }).fill('Learning 日本語 / 中文')
    await page.getByLabel('Project type').selectOption('learning')
    await page.getByLabel('Role', { exact: true }).fill('Individual contributor')
    await page.getByLabel('Start precision').selectOption('year')
    await page.getByLabel('Start date').fill('2025')
    await page.getByLabel('Ongoing', { exact: true }).check()
    const created = await waitForOperation(page, 'create_project', () =>
        page.getByRole('button', { name: 'Create project', exact: true }).click(),
    )
    expect(await owner.op('get_project', { id: created.id })).toMatchObject({
        title: 'Learning 日本語 / 中文',
        type: 'learning',
        role: 'Individual contributor',
        occurrence: { start: '2025', precision: 'year', ongoing: true },
    })
    await expect(page.getByRole('heading', { name: created.title })).toBeVisible()
    await page.getByRole('button', { name: 'Edit project', exact: true }).click()
    await page.getByLabel('Title', { exact: true }).fill('Updated learning project')
    await page
        .getByLabel('Background', { exact: true })
        .fill('Project context, separate from personal actions.')
    await page.getByLabel('Team outcome').fill('The group completed a prototype.')
    await waitForOperation(page, 'update_project', () =>
        page.getByRole('button', { name: 'Save project' }).click(),
    )
    expect(await owner.op('get_project', { id: created.id })).toMatchObject({
        title: 'Updated learning project',
        background: 'Project context, separate from personal actions.',
        team_outcome: 'The group completed a prototype.',
    })
})

test('PROJECT-02 required fields and invalid date ranges cannot create a project', async ({
    page,
    owner,
}) => {
    await page.goto('/projects/new')
    await page.getByRole('button', { name: 'Create project', exact: true }).click()
    await expect(page.getByRole('alert')).toBeVisible()
    await expect(page.getByLabel('Title', { exact: true })).toBeFocused()
    expect((await owner.op('list_projects')).total).toBe(0)
    await page.getByLabel('Title', { exact: true }).fill('Invalid time')
    await page.getByLabel('Role', { exact: true }).fill('Developer')
    await page.getByLabel('Start precision').selectOption('month')
    await page.getByLabel('Start date').fill('2026-11')
    await page.getByLabel('End precision').selectOption('month')
    await page.getByLabel('End date').fill('2025-10')
    await page.getByRole('button', { name: 'Create project', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/date|end|start|range/i)
    expect((await owner.op('list_projects')).total).toBe(0)
})

test('PROJECT-03 search/type filters, no results and pagination keep all records reachable', async ({
    page,
    owner,
}) => {
    await projectFixture(owner)
    await projectFixture(owner, { title: 'Commercial system', type: 'commercial' })
    for (let i = 0; i < 20; i++)
        await projectFixture(owner, { title: `Learning project ${i}`, type: 'learning' })
    await page.goto('/projects')
    await expect(page.getByRole('button', { name: 'Next page' })).toBeEnabled()
    await page.getByRole('button', { name: 'Next page' }).click()
    await expect(page.getByRole('link', { name: 'Atlas Notes', exact: true })).toBeVisible()
    await page.getByLabel('Project type').selectOption('commercial')
    await expect(page.getByRole('link', { name: 'Commercial system', exact: true })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Atlas Notes', exact: true })).toHaveCount(0)
    await page.getByLabel('Search projects').fill('No such project')
    await expect(page.getByText('No projects match your filters.')).toBeVisible()
    await page.getByRole('button', { name: 'Clear filters' }).click()
    await expect(page.getByRole('link', { name: 'Learning project 19', exact: true })).toBeVisible()
})

test('PROJECT-04 archive, restore and confirmed deletion have distinct effects', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    await experienceFixture(owner, project.id)
    await page.goto(`/projects/${project.id}`)
    await waitForOperation(page, 'archive_project', () =>
        page.getByRole('button', { name: 'Archive project' }).click(),
    )
    expect((await owner.op('get_project', { id: project.id })).archived_at).not.toBeNull()
    await page.goto('/projects')
    await page.getByLabel('Project status').selectOption('archived')
    await page.getByRole('link', { name: 'Atlas Notes', exact: true }).click()
    await waitForOperation(page, 'restore_project', () =>
        page.getByRole('button', { name: 'Restore project' }).click(),
    )
    expect((await owner.op('get_project', { id: project.id })).archived_at).toBeNull()
    await page.getByRole('button', { name: 'Delete project' }).click()
    await page
        .getByRole('dialog', { name: 'Delete project?' })
        .getByRole('button', { name: 'Cancel' })
        .click()
    expect((await owner.op('list_projects')).total).toBe(1)
    await page.getByRole('button', { name: 'Delete project' }).click()
    await waitForOperation(page, 'delete_project', () =>
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Delete project', exact: true })
            .click(),
    )
    expect((await owner.op('list_projects')).total).toBe(0)
})

test('PROJECT-05 unavailable records show a neutral page without fixture fallback', async ({
    page,
    owner,
}) => {
    await page.goto('/projects/00000000-0000-4000-8000-000000000001')
    await expect(page.getByRole('heading', { name: 'Content unavailable' })).toBeVisible()
    await expect(page.getByText('Atlas Notes')).toHaveCount(0)
})
