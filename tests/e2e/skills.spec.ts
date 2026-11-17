import { test, expect, projectFixture, experienceFixture, waitForOperation } from './helpers'

test('SKILL-01 canonical skills, aliases and collisions are persisted and searchable', async ({
    page,
    owner,
}) => {
    await page.goto('/skills')
    await page.getByRole('button', { name: 'Add skill', exact: true }).click()
    await page.getByRole('dialog').getByLabel('Skill name').fill('Vue 3')
    const skill = await waitForOperation(page, 'create_skill', () =>
        page.getByRole('dialog').getByRole('button', { name: 'Save skill' }).click(),
    )
    await page.getByRole('link', { name: 'Vue 3', exact: true }).click()
    await page.getByLabel('New alias').fill('VueJS')
    await waitForOperation(page, 'add_skill_alias', () =>
        page.getByRole('button', { name: 'Add alias' }).click(),
    )
    expect((await owner.op('get_skill', { id: skill.id })).aliases).toContain('vuejs')
    await page.goto('/skills')
    await page.getByLabel('Search skills').fill('vuejs')
    await expect(page.getByRole('link', { name: 'Vue 3', exact: true })).toBeVisible()
    await page.getByRole('link', { name: 'Vue 3', exact: true }).click()
    await page.getByLabel('Skill name').fill('Vue')
    await waitForOperation(page, 'update_skill', () =>
        page.getByRole('button', { name: 'Rename skill' }).click(),
    )
    expect((await owner.op('get_skill', { id: skill.id })).name).toBe('Vue')
    await waitForOperation(page, 'delete_skill_alias', () =>
        page.getByRole('button', { name: 'Remove alias vuejs' }).click(),
    )
    expect((await owner.op('get_skill', { id: skill.id })).aliases).toEqual([])
    await owner.op('create_skill', { name: 'TypeScript' })
    await page.getByLabel('New alias').fill('TypeScript')
    await page.getByRole('button', { name: 'Add alias' }).click()
    await expect(page.getByRole('alert')).toContainText(/assigned|already/i)
})

test('SKILL-02 attach a normalized skill once, browse its supporting work, then detach', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await page.getByRole('button', { name: 'Add skill', exact: true }).click()
    let dialog = page.getByRole('dialog', { name: 'Connect a skill' })
    await dialog.getByLabel('Skill name').fill('Vue 3')
    await waitForOperation(page, 'attach_skill', () =>
        dialog.getByRole('button', { name: 'Connect skill' }).click(),
    )
    let stored = await owner.op('get_experience', { id: experience.id })
    expect(stored.skills).toHaveLength(1)
    const skillId = stored.skills[0].id
    await page.getByRole('button', { name: 'Add skill', exact: true }).click()
    dialog = page.getByRole('dialog', { name: 'Connect a skill' })
    await dialog.getByLabel('Skill name').fill('  vue  3  ')
    await dialog.getByRole('button', { name: 'Connect skill' }).click()
    await expect(dialog).toBeHidden()
    stored = await owner.op('get_experience', { id: experience.id })
    expect(stored.skills).toHaveLength(1)
    expect(await owner.op('get_skill', { id: skillId })).toMatchObject({
        experience_count: 1,
        project_count: 1,
    })
    await page.goto(`/skills/${skillId}`)
    await page.getByRole('link', { name: experience.title, exact: true }).click()
    await expect(page).toHaveURL(new RegExp(`/experiences/${experience.id}$`))
    await waitForOperation(page, 'detach_skill', () =>
        page.getByRole('button', { name: 'Remove Vue 3', exact: true }).click(),
    )
    expect((await owner.op('get_experience', { id: experience.id })).skills).toEqual([])
    expect(await owner.op('get_skill', { id: skillId })).toMatchObject({
        experience_count: 0,
        project_count: 0,
    })
})
