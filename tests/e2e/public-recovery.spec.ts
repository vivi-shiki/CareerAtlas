import {
    test,
    expect,
    register,
    post,
    projectFixture,
    experienceFixture,
    publishedFixture,
    noOverflow,
    waitForOperation,
} from './helpers'

test('PUBLIC-01 project and skill browsing use permitted snapshots and source dialogs restore focus', async ({
    page,
    owner,
    browser,
}) => {
    const { publication, experience, skill } = await publishedFixture(owner)
    const context = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    const visitor = await context.newPage()
    const externalRequests: string[] = []
    visitor.on('request', (request) => {
        if (!request.url().startsWith(String(test.info().project.use.baseURL)))
            externalRequests.push(request.url())
    })
    await visitor.goto(`/p/${publication.slug}`)
    await visitor.getByRole('button', { name: 'Read project' }).click()
    await expect(visitor.getByRole('dialog')).toContainText(experience.title)
    await visitor.keyboard.press('Escape')
    await expect(visitor.getByRole('button', { name: 'Read project' })).toBeFocused()
    await visitor.getByRole('button', { name: 'Vue 3', exact: true }).click()
    await expect(visitor.getByRole('dialog')).toContainText('1 project')
    await expect(visitor.getByRole('dialog')).toContainText(experience.title)
    await visitor.keyboard.press('Escape')
    await expect(visitor.getByRole('link', { name: /Pull request #24/ })).toHaveAttribute(
        'rel',
        /noopener/,
    )
    expect(externalRequests).toEqual([])
    await context.close()
})

test('PUBLIC-02 archiving keeps snapshots readable but source deletion removes access', async ({
    page,
    owner,
}) => {
    const { publication, experience } = await publishedFixture(owner)
    await page.goto(`/experiences/${experience.id}`)
    await waitForOperation(page, 'archive_experience', () =>
        page.getByRole('button', { name: 'Archive experience' }).click(),
    )
    expect(
        (await (await page.request.get(`/api/public/${publication.slug}`)).json()).data.items,
    ).toHaveLength(1)
    await page.getByRole('button', { name: 'Delete experience' }).click()
    await waitForOperation(page, 'delete_experience', () =>
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Delete experience', exact: true })
            .click(),
    )
    expect(
        (await (await page.request.get(`/api/public/${publication.slug}`)).json()).data.items,
    ).toEqual([])
    await page.goto(`/p/${publication.slug}`)
    await expect(page.getByText('No published work is available.')).toBeVisible()
})

test('BOUNDARY-01 foreign private links disclose neither title nor evidence', async ({
    page,
    owner,
    browser,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    const context = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    await register(context.request)
    const foreign = await context.newPage()
    for (const path of [`/projects/${project.id}`, `/experiences/${experience.id}`]) {
        await foreign.goto(path)
        await expect(foreign.getByRole('heading', { name: 'Content unavailable' })).toBeVisible()
        await expect(foreign.getByText('Atlas Notes')).toHaveCount(0)
        await expect(foreign.getByText('Accessible project search')).toHaveCount(0)
    }
    await context.close()
})

test('RECOVERY-01 session expiry preserves unsaved content and cannot mutate data', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    await page
        .getByLabel('My contribution', { exact: true })
        .fill('Unsaved when the session expires')
    await post(page.request, '/api/auth/logout')
    await page.getByRole('button', { name: 'Save draft', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText(/session|sign in/i)
    await expect(page.getByRole('link', { name: 'Sign in again in a new tab' })).toHaveAttribute(
        'target',
        '_blank',
    )
    await expect(page.getByLabel('My contribution', { exact: true })).toHaveValue(
        'Unsaved when the session expires',
    )
    await post(page.request, '/api/auth/login', { email: owner.email, password: owner.password })
    expect((await owner.op('get_experience', { id: experience.id })).personal_action).toBe(
        experience.personal_action,
    )
})

test('RECOVERY-02 an API outage has a retry state, never synthetic fallback', async ({
    page,
    owner,
}) => {
    await projectFixture(owner, { title: 'Real persisted test project' })
    await page.route(
        '**/api/operations/list_projects',
        (route) =>
            route.fulfill({
                status: 503,
                contentType: 'application/json',
                body: JSON.stringify({ message: 'Temporarily unavailable.' }),
            }),
        { times: 1 },
    )
    await page.goto('/projects')
    await expect(page.getByRole('alert')).toContainText(/unavailable|load/i)
    await expect(page.getByText('Atlas Notes')).toHaveCount(0)
    await page.getByRole('button', { name: 'Retry', exact: true }).click()
    await expect(
        page.getByRole('link', { name: 'Real persisted test project', exact: true }),
    ).toBeVisible()
})

test('ACCESSIBILITY-01 long multilingual content fits desktop, reduced-motion mobile and narrow reflow', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner, {
        title: '長いプロジェクト名と中文项目名称'.repeat(7),
    })
    const experience = await experienceFixture(owner, project.id, {
        personal_action: '日本語・中文の説明とアクセシビリティ。'.repeat(30),
    })
    for (const width of [1487, 743, 390]) {
        await page.setViewportSize({ width, height: 900 })
        await page.emulateMedia({ reducedMotion: 'reduce' })
        await page.goto(`/experiences/${experience.id}`)
        await expect(page.getByLabel('My contribution', { exact: true })).toHaveValue(
            experience.personal_action,
        )
        await noOverflow(page)
        await expect(page.getByRole('button', { name: 'Save draft', exact: true })).toBeVisible()
        for (const name of ['Save draft', 'Confirm experience']) {
            const button = page.getByRole('button', { name, exact: true })
            const bounds = await button.boundingBox()
            expect(bounds?.height).toBeGreaterThanOrEqual(40)
            expect(bounds?.height).toBeLessThanOrEqual(88)
        }
    }
})

test('ACCESSIBILITY-02 keyboard can open, cancel and return focus from an evidence dialog', async ({
    page,
    owner,
}) => {
    const project = await projectFixture(owner)
    const experience = await experienceFixture(owner, project.id)
    await page.goto(`/experiences/${experience.id}`)
    const trigger = page.getByRole('button', { name: 'Add evidence', exact: true })
    await trigger.focus()
    await page.keyboard.press('Enter')
    const dialog = page.getByRole('dialog', { name: 'Add evidence' })
    await expect(dialog).toBeVisible()
    await expect(dialog.getByLabel('Evidence type')).toBeFocused()
    await page.keyboard.press('Escape')
    await expect(dialog).toBeHidden()
    await expect(trigger).toBeFocused()
})
