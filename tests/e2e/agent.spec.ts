import { test, expect, publishedFixture, post, noOverflow } from './helpers'

test('AGENT-06B ambiguous submission retry reuses request ID and does not duplicate the run', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    let lost = false
    const requestIds: string[] = []
    await page.route('**/agent/conversations/*/runs', async (route) => {
        requestIds.push(route.request().postDataJSON().request_id)
        if (!lost) {
            lost = true
            await route.fetch()
            await route.abort('failed')
        } else await route.continue()
    })
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('What did they build?')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText('network connection')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 20000 })
    expect(requestIds).toHaveLength(2)
    expect(requestIds[0]).toBe(requestIds[1])
    await page.reload()
    await expect(page.locator('.agent-turn')).toHaveCount(1)
})

test('AGENT-06C request throttling preserves input and manual source browsing', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.route('**/agent/conversations/*/runs', (route) =>
        route.fulfill({
            status: 429,
            json: {
                code: 'rate_limited',
                message: 'Question limit reached. Please try again later.',
            },
        }),
    )
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('Keep this question')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText('Question limit reached')
    await expect(page.getByLabel('Your question', { exact: true })).toHaveValue(
        'Keep this question',
    )
    await page.getByRole('button', { name: 'Read project' }).click()
    await expect(page.getByRole('dialog')).toBeVisible()
})

test('AGENT-10B long answers and multiple turns remain escaped and contained', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('What did they build?')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 20000 })
    await page.route('**/agent/conversations/*', async (route) => {
        if (route.request().method() !== 'GET') return route.continue()
        const response = await route.fetch()
        const body = await response.json()
        body.data.runs[0].answer.answer =
            '<script>window.injection=true</script>' + 'LongUnbrokenText'.repeat(120)
        body.data.runs.push({ ...body.data.runs[0], id: 'second-visual-fixture' })
        await route.fulfill({ response, json: body })
    })
    await page.evaluate(() => window.dispatchEvent(new Event('focus')))
    await expect(page.locator('.agent-answer')).toHaveCount(2)
    for (const width of [1487, 768, 390]) {
        await page.setViewportSize({ width, height: 1058 })
        await noOverflow(page)
    }
    expect(await page.evaluate(() => (window as any).injection)).toBeUndefined()
})

test('AGENT-01 disabled service keeps manual browsing available', async ({ page, owner }) => {
    const { publication } = await publishedFixture(owner)
    await page.route('**/agent', (route) =>
        route.fulfill({ json: { data: { available: false, max_input: 2000 } } }),
    )
    await page.goto(`/p/${publication.slug}`)
    await expect(
        page.getByText('Questions are unavailable right now.', { exact: false }),
    ).toBeVisible()
    await page.getByRole('button', { name: 'Read project' }).click()
    await expect(page.getByRole('dialog')).toContainText('Accessible project search')
})

test('AGENT-02 suggestions, input limits, newline and IME do not accidentally submit', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    const input = page.getByLabel('Your question', { exact: true })
    const send = page.getByRole('button', { name: 'Ask question', exact: true })
    await expect(send).toBeDisabled()
    await page.getByRole('button', { name: 'What did this person build?', exact: true }).click()
    await expect(input).toBeFocused()
    await expect(input).toHaveValue('What did this person build?')
    await input.press('Shift+Enter')
    await expect(input).toHaveValue('What did this person build?\n')
    await input.dispatchEvent('keydown', { key: 'Enter', isComposing: true })
    await expect(page.locator('.agent-turn')).toHaveCount(0)
    await input.fill('x'.repeat(2001))
    await expect(send).toBeDisabled()
    await input.fill('   ')
    await expect(send).toBeDisabled()
})

test('AGENT-03-04 real queued workflow displays answer, citation and keyboard source dialog', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('What did the person build?')
    await page.getByLabel('Your question', { exact: true }).press('Enter')
    await expect(page.locator('.agent-turn[data-state="completed"]')).toBeVisible({
        timeout: 20000,
    })
    await expect(page.locator('.agent-answer')).toContainText('keyboard navigation')
    await expect(page.locator('.agent-panel')).not.toContainText('PRIVATE_SENTINEL')
    const source = page.locator('.agent-citations button').first()
    await source.click()
    await expect(page.getByRole('dialog')).toContainText('I built keyboard navigation')
    await page.keyboard.press('Escape')
    await expect(source).toBeFocused()
    await expect(page.locator('.agent-panel')).toContainText(
        'What personal actions are documented?',
    )
    const published = (await (await page.request.get(`/api/public/${publication.slug}`)).json())
        .data
    // Controlled model output exercises profile citations in the real source dialog.
    await page.route('**/agent/conversations/*', async (route) => {
        if (route.request().method() !== 'GET') return route.continue()
        const response = await route.fetch()
        const body = await response.json()
        body.data.runs[0].answer.citations = [
            {
                handle: 'profile-ui-fixture',
                experience_id: null,
                title: 'Published profile',
                version: published.version,
                field: 'display_name',
                excerpt: published.profile.display_name,
            },
        ]
        await route.fulfill({ response, json: body })
    })
    await page.evaluate(() => window.dispatchEvent(new Event('focus')))
    const profileSource = page
        .locator('.agent-citations button')
        .filter({ hasText: 'Published profile' })
    await profileSource.click()
    await expect(page.getByRole('dialog')).toContainText(published.profile.display_name)
    await page.keyboard.press('Escape')
    // Backend version drift has separate integration coverage; this verifies the UI guard.
    await page.route(`**/api/public/${publication.slug}`, (route) =>
        route.fulfill({ json: { data: { ...published, version: published.version + 1 } } }),
    )
    await profileSource.click()
    await expect(page.getByRole('dialog')).toContainText('Published content changed')
    await expect(page.getByRole('dialog')).not.toContainText(published.profile.display_name)
})

test('AGENT-05 cancellation suppresses delayed answer and duplicate submit', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('What did they build? [test:slow]')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.getByLabel('Your question', { exact: true })).toBeDisabled()
    await page.getByRole('button', { name: 'Cancel answer', exact: true }).click()
    await expect(page.locator('.agent-turn[data-state="cancelled"]')).toBeVisible()
    await expect(page.locator('.agent-answer')).toHaveCount(0)
    await expect(page.getByRole('button', { name: 'Ask question', exact: true })).toBeEnabled()
})

test('AGENT-06 failure preserves question and explicit retry creates another run', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    const input = page.getByLabel('Your question', { exact: true })
    await input.fill('Question [test:failure]')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.locator('.agent-turn[data-state="failed"]')).toHaveCount(1, {
        timeout: 20000,
    })
    await expect(input).toHaveValue('Question [test:failure]')
    await page.getByRole('button', { name: 'Retry question', exact: true }).click()
    await expect(page.locator('.agent-turn[data-state="failed"]')).toHaveCount(2, {
        timeout: 20000,
    })
    await page.getByRole('button', { name: 'Read project' }).click()
    await expect(page.getByRole('dialog')).toBeVisible()
})

test('AGENT-07 revoke clears previously displayed answer on next authorization check', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('What did they build?')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 20000 })
    await owner.op('revoke_publication', {
        id: publication.id,
        expected_version: publication.version,
    })
    await page.evaluate(() => window.dispatchEvent(new Event('focus')))
    await expect(page.getByRole('alert')).toContainText('Previous answers have been cleared')
    await expect(page.locator('.agent-answer')).toHaveCount(0)
    await expect(page.locator('.agent-citations')).toHaveCount(0)
})

test('AGENT-08 browser sessions cannot share conversation access', async ({
    page,
    owner,
    browser,
}) => {
    const { publication } = await publishedFixture(owner)
    const base = `/api/public/${publication.slug}/agent`
    const created = await post(page.request, `${base}/conversations`)
    expect(created.ok()).toBeTruthy()
    const id = (await created.json()).data.id
    const other = await browser.newContext({ baseURL: test.info().project.use.baseURL })
    expect((await other.request.get(`${base}/conversations/${id}`)).status()).toBe(404)
    await other.close()
})

test('AGENT-09 same-tab refresh recovers history; new conversation and gap answer work', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.goto(`/p/${publication.slug}`)
    await page.getByLabel('Your question', { exact: true }).fill('A missing skill?')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.getByText('Information gap', { exact: true })).toBeVisible({ timeout: 20000 })
    await expect(page.locator('.agent-citations')).toHaveCount(0)
    await page.reload()
    await expect(page.locator('.agent-answer')).toContainText('do not establish')
    await page.getByRole('button', { name: 'New conversation', exact: true }).click()
    await expect(page.locator('.agent-turn')).toHaveCount(0)
    await expect(page.getByLabel('Your question', { exact: true })).toHaveValue('')
})

test('AGENT-10 mobile answer and sources remain readable without overflow', async ({
    page,
    owner,
}) => {
    const { publication } = await publishedFixture(owner)
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto(`/p/${publication.slug}`)
    await page.getByRole('link', { name: 'Ask', exact: true }).click()
    await expect(page).toHaveURL(/#ask$/)
    await page.getByLabel('Your question', { exact: true }).fill('What did they build?')
    await page.getByRole('button', { name: 'Ask question', exact: true }).click()
    await expect(page.locator('.agent-answer')).toBeVisible({ timeout: 20000 })
    await noOverflow(page)
    const rect = await page.getByRole('button', { name: 'Ask question', exact: true }).boundingBox()
    expect(rect!.height).toBeGreaterThanOrEqual(44)
    await page.locator('.agent-citations button').click()
    await expect(page.getByRole('dialog')).toContainText('Accessible project search')
    await noOverflow(page)
})
