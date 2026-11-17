import { test, expect, post, operation, waitForOperation } from './helpers'
import { randomUUID } from 'node:crypto'

test('AUTH-01 registration, logout and login persist the owner session', async ({ page }) => {
    const email = `ui-${randomUUID()}@example.test`
    await page.goto('/register')
    await page.getByLabel('Display name', { exact: true }).fill('Browser Owner')
    await page.getByLabel('Email', { exact: true }).fill(email)
    await page.getByLabel('Password', { exact: true }).fill('FictionalPassword!2026')
    await page.getByLabel('Confirm password', { exact: true }).fill('FictionalPassword!2026')
    await page.getByRole('button', { name: 'Create account', exact: true }).click()
    await expect(page).toHaveURL(/\/projects$/)
    expect((await (await page.request.get('/api/auth/me')).json()).data.email).toBe(email)
    await expect(page.getByRole('heading', { name: 'Start with a project' })).toBeVisible()
    await page.getByRole('button', { name: 'Sign out', exact: true }).click()
    await expect(page).toHaveURL(/\/login$/)
    expect(
        (
            await page.request.get('/api/auth/me', { headers: { Accept: 'application/json' } })
        ).status(),
    ).toBe(401)
    await page.getByLabel('Email', { exact: true }).fill(email)
    await page.getByLabel('Password', { exact: true }).fill('wrong-password')
    await page.getByRole('button', { name: 'Sign in', exact: true }).click()
    await expect(page.getByText('The credentials do not match.')).toBeVisible()
    await page.getByLabel('Password', { exact: true }).fill('FictionalPassword!2026')
    await page.getByRole('button', { name: 'Sign in', exact: true }).click()
    await expect(page).toHaveURL(/\/projects$/)
})

test('AUTH-02 validation and duplicate registration never replace the existing identity', async ({
    page,
    owner,
}) => {
    await post(owner.request, '/api/auth/logout')
    await page.goto('/register')
    await page.getByLabel('Display name', { exact: true }).fill('Another owner')
    await page.getByLabel('Email', { exact: true }).fill(owner.email)
    await page.getByLabel('Password', { exact: true }).fill('short')
    await page.getByLabel('Confirm password', { exact: true }).fill('different')
    await page.getByRole('button', { name: 'Create account', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/email|password/i)
    await expect(page).toHaveURL(/\/register$/)
    expect(
        (
            await page.request.get('/api/auth/me', { headers: { Accept: 'application/json' } })
        ).status(),
    ).toBe(401)
})

test('AUTH-03 unauthenticated owner routes lead to sign in without private content', async ({
    page,
}) => {
    await page.goto('/profile')
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByRole('heading', { name: /Sign in|Your work, connected/ })).toBeVisible()
})

test('AUTH-04 cancelling sign out preserves both the session and unsaved profile', async ({
    page,
    owner,
}) => {
    await page.goto('/profile')
    await page.getByLabel('Bio', { exact: true }).fill('Unsaved before sign out')
    await page.getByRole('button', { name: 'Sign out', exact: true }).click()
    await page
        .getByRole('dialog', { name: 'Discard unsaved changes?' })
        .getByRole('button', { name: 'Keep editing' })
        .click()
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue('Unsaved before sign out')
    expect((await (await page.request.get('/api/auth/me')).json()).data.email).toBe(owner.email)
    await page.getByRole('button', { name: 'Sign out', exact: true }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Discard changes' }).click()
    await expect(page).toHaveURL(/\/login$/)
    expect(
        (
            await page.request.get('/api/auth/me', { headers: { Accept: 'application/json' } })
        ).status(),
    ).toBe(401)
})

test('PROFILE-01 multilingual profile and selected contacts survive reload', async ({
    page,
    owner,
}) => {
    await page.goto('/profile')
    await page.getByLabel('Display name', { exact: true }).fill('Alex / 李明 / アレックス')
    await page.getByLabel('Career direction').fill('Frontend developer')
    await page
        .getByLabel('Bio', { exact: true })
        .fill('日本語・中文の経験を記録します。\nI build accessible interfaces.')
    await page.getByLabel('Preferred language').selectOption('ja')
    await page.getByRole('button', { name: 'Add contact link' }).click()
    await page.getByLabel('Contact label 1').fill('Portfolio')
    await page.getByLabel('Contact URL 1').fill('https://example.com/work')
    await waitForOperation(page, 'update_profile', () =>
        page.getByRole('button', { name: 'Save profile' }).click(),
    )
    const stored = await owner.op('get_profile')
    expect(stored).toMatchObject({
        display_name: 'Alex / 李明 / アレックス',
        preferred_language: 'ja',
        contact_links: [{ label: 'Portfolio', url: 'https://example.com/work' }],
    })
    expect(stored.biography).toContain('\n')
    await page.reload()
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue(stored.biography)
    await page.getByRole('button', { name: 'Remove contact 1' }).click()
    await waitForOperation(page, 'update_profile', () =>
        page.getByRole('button', { name: 'Save profile' }).click(),
    )
    expect((await owner.op('get_profile')).contact_links).toEqual([])
})

test('PROFILE-02 invalid contacts are rejected and form values are retained', async ({
    page,
    owner,
}) => {
    const before = await owner.op('get_profile')
    await page.goto('/profile')
    await page.getByRole('button', { name: 'Add contact link' }).click()
    await page.getByLabel('Contact label 1').fill('Unsafe link')
    await page.getByLabel('Contact URL 1').fill('javascript:alert(1)')
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('alert')).toContainText(/http|URL|link/i)
    await expect(page.getByLabel('Contact URL 1')).toHaveValue('javascript:alert(1)')
    expect((await owner.op('get_profile')).version).toBe(before.version)
})

test('PROFILE-03 stale profile cannot overwrite the newer revision', async ({ page, owner }) => {
    const before = await owner.op('get_profile')
    await page.goto('/profile')
    await expect(page.getByLabel('Display name', { exact: true })).toHaveValue(before.display_name)
    await page.getByLabel('Bio', { exact: true }).fill('My unsaved biography')
    await owner.op('update_profile', {
        expected_version: before.version,
        biography: 'A newer biography',
    })
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('alert')).toContainText(/newer|changed|version/i)
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue('My unsaved biography')
    expect((await owner.op('get_profile')).biography).toBe('A newer biography')
})

test('PROFILE-04 failed save retries without losing input; leaving unsaved changes is explicit', async ({
    page,
    owner,
}) => {
    await page.goto('/profile')
    await page.getByLabel('Bio', { exact: true }).fill('A biography worth retaining')
    await page.route(
        '**/api/operations/update_profile',
        (route) =>
            route.fulfill({
                status: 503,
                contentType: 'application/json',
                body: JSON.stringify({ message: 'Temporarily unavailable.' }),
            }),
        { times: 1 },
    )
    await page.getByRole('button', { name: 'Save profile' }).click()
    await expect(page.getByRole('alert')).toContainText(/unavailable|save|retry/i)
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue('A biography worth retaining')
    await waitForOperation(page, 'update_profile', () =>
        page.getByRole('button', { name: 'Save profile' }).click(),
    )
    expect((await owner.op('get_profile')).biography).toBe('A biography worth retaining')
    await page.getByLabel('Bio', { exact: true }).fill('Unsaved second edit')
    await page
        .getByRole('navigation', { name: 'Workspace' })
        .getByRole('link', { name: 'Projects' })
        .click()
    const dialog = page.getByRole('dialog', { name: 'Discard unsaved changes?' })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: 'Keep editing' }).click()
    await expect(page.getByLabel('Bio', { exact: true })).toHaveValue('Unsaved second edit')
    await page
        .getByRole('navigation', { name: 'Workspace' })
        .getByRole('link', { name: 'Projects' })
        .click()
    await page.getByRole('dialog').getByRole('button', { name: 'Discard changes' }).click()
    await expect(page).toHaveURL(/\/projects$/)
})
