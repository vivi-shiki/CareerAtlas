import { randomUUID } from 'node:crypto'
import { test, expect, projectFixture, experienceFixture } from './helpers'

test('REVIEW-13 a pending save prevents edits from being silently marked as saved', async ({
    page,
    owner,
}) => {
    await page.goto('/profile')
    const bio = page.getByLabel('Bio', { exact: true })
    await bio.fill('The submitted biography')
    let release!: () => void
    let started!: () => void
    const held = new Promise<void>((resolve) => {
        release = resolve
    })
    const pending = new Promise<void>((resolve) => {
        started = resolve
    })
    await page.route(
        '**/api/operations/update_profile',
        async (route) => {
            started()
            await held
            await route.continue()
        },
        { times: 1 },
    )
    await page.getByRole('button', { name: 'Save profile' }).click()
    await pending
    try {
        await expect(bio).toBeDisabled()
    } finally {
        release()
    }
    await expect(page.getByRole('status')).toContainText('Profile saved.')
    await expect(bio).toBeEnabled()
    expect((await owner.op('get_profile')).biography).toBe('The submitted biography')
    await bio.fill('A new unsaved edit')
    await expect(page.getByText('Unsaved changes · Private', { exact: true })).toBeVisible()
})

test('REVIEW-12 shared project context and each experience date survive distinct field selections', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const first = await experienceFixture(owner, project.id, {
        title: 'First contribution',
        occurrence: { start: '2025', precision: 'year' },
    })
    const second = await experienceFixture(owner, project.id, {
        title: 'Second contribution',
        occurrence: { start: '2026-10', precision: 'month' },
    })
    const selection = {
        profile_fields: ['display_name'],
        items: [
            { experience_id: first.id, fields: ['title', 'occurrence'], project_fields: [] },
            {
                experience_id: second.id,
                fields: ['title', 'occurrence'],
                project_fields: ['title', 'role', 'background'],
            },
        ],
    }
    const preview = await owner.op('preview_publication', selection)
    const publication = await owner.op('publish_publication', {
        ...selection,
        preview_digest: preview.digest,
        idempotency_key: randomUUID(),
    })
    await page.goto(`/p/${publication.slug}`)
    await expect(page.getByRole('heading', { name: 'Atlas Notes', exact: true })).toBeVisible()
    await expect(page.getByText(project.background, { exact: true })).toBeVisible()
    await expect(page.getByText(`Role: ${project.role}`, { exact: true })).toBeVisible()
    const experiences = page.locator('.published-experience')
    await expect(experiences.filter({ hasText: 'First contribution' })).toContainText('2025')
    await expect(experiences.filter({ hasText: 'Second contribution' })).toContainText(
        'October 2026',
    )
    await expect(page.getByText(first.personal_action, { exact: true })).toHaveCount(0)
    await expect(page.getByText(project.team_outcome, { exact: true })).toHaveCount(0)
})
