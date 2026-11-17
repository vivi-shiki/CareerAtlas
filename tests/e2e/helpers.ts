import { test as base, expect, type APIRequestContext, type Page } from '@playwright/test'
import { randomUUID } from 'node:crypto'
import { spawn } from 'node:child_process'

export type Data = Record<string, any>
export type Owner = {
    name: string
    email: string
    password: string
    request: APIRequestContext
    op: (operation: string, input?: Data) => Promise<any>
}

const csrfTokens = new WeakMap<APIRequestContext, string>()

export async function post(request: APIRequestContext, path: string, data: Data = {}) {
    let token = csrfTokens.get(request)
    if (!token) {
        const response = await request.get('/api/csrf', { headers: { Accept: 'application/json' } })
        expect(response.ok()).toBeTruthy()
        token = (await response.json()).csrf_token as string
        csrfTokens.set(request, token)
    }
    const response = await request.post(path, {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
        data,
    })
    if (path.startsWith('/api/auth/')) csrfTokens.delete(request)
    return response
}

export async function operation(request: APIRequestContext, name: string, input: Data = {}) {
    const response = await post(request, `/api/operations/${name}`, input)
    expect(
        response.ok(),
        `${name} returned ${response.status()}: ${await response.text()}`,
    ).toBeTruthy()
    return (await response.json()).data
}

export async function register(request: APIRequestContext): Promise<Owner> {
    const owner = {
        name: 'Fictional Test Owner',
        email: `browser-${randomUUID()}@example.test`,
        password: 'FictionalPassword!2026',
        request,
        op: (name: string, data: Data = {}) => operation(request, name, data),
    }
    const response = await post(request, '/api/auth/register', {
        name: owner.name,
        email: owner.email,
        password: owner.password,
        password_confirmation: owner.password,
    })
    expect(response.status(), await response.text()).toBe(201)
    return owner
}

export const test = base.extend<{ owner: Owner }>({
    owner: async ({ page }, use) => {
        const owner = await register(page.request)
        await use(owner)
    },
})
export { expect }

export async function projectFixture(owner: Owner, overrides: Data = {}) {
    return owner.op('create_project', {
        title: 'Atlas Notes',
        type: 'personal',
        role: 'Frontend developer',
        background: 'A searchable workspace for project notes.',
        team_outcome: 'The team released the search workflow.',
        ...overrides,
    })
}

export async function experienceFixture(
    owner: Owner,
    projectId: string,
    overrides: Data = {},
    confirm = true,
) {
    let experience = await owner.op('create_experience', {
        project_id: projectId,
        title: 'Accessible project search',
        record_type: 'work_contribution',
        occurrence: { start: '2026-10', precision: 'month' },
        personal_action: 'I built keyboard navigation and component tests.',
        private_notes: 'PRIVATE_SENTINEL_NOT_FOR_PUBLIC',
        ...overrides,
    })
    if (confirm)
        experience = await owner.op('confirm_experience', {
            id: experience.id,
            expected_version: experience.version,
        })
    return experience
}

export function selection(experienceId: string) {
    return {
        profile_fields: ['display_name', 'direction', 'biography'],
        items: [
            {
                experience_id: experienceId,
                fields: [
                    'title',
                    'record_type',
                    'occurrence',
                    'personal_action',
                    'skills',
                    'evidence',
                ],
                project_fields: ['title', 'type', 'role', 'background'],
            },
        ],
    }
}

export async function publishedFixture(owner: Owner) {
    const project = await projectFixture(owner)
    let experience = await experienceFixture(owner, project.id, {}, false)
    const skill = await owner.op('create_skill', { name: 'Vue 3' })
    experience = await owner.op('attach_skill', {
        id: experience.id,
        skill_id: skill.id,
        expected_version: experience.version,
    })
    await owner.op('create_evidence', {
        experience_id: experience.id,
        expected_version: experience.version,
        kind: 'repository',
        label: 'Pull request #24',
        url: 'https://example.com/pull/24',
    })
    experience = await owner.op('get_experience', { id: experience.id })
    experience = await owner.op('confirm_experience', {
        id: experience.id,
        expected_version: experience.version,
    })
    const input = selection(experience.id)
    const preview = await owner.op('preview_publication', input)
    const publication = await owner.op('publish_publication', {
        ...input,
        preview_digest: preview.digest,
        idempotency_key: randomUUID(),
    })
    return { project, experience, skill, publication, preview }
}

export async function waitForOperation(
    page: Page,
    name: string,
    action: () => Promise<unknown>,
    status = 200,
) {
    const pending = page.waitForResponse(
        (response) =>
            response.url().endsWith(`/api/operations/${name}`) &&
            response.request().method() === 'POST',
    )
    await action()
    const response = await pending
    expect(response.status()).toBe(status)
    return (await response.json()).data
}

export async function acceptDialog(page: Page, title: string, confirm: string) {
    const dialog = page.getByRole('dialog', { name: title })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: confirm, exact: true }).click()
}

export async function noOverflow(page: Page) {
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false)
}

export async function databaseObservation(owner: Owner): Promise<Record<string, number>> {
    const env = { ...process.env, APP_ENV: 'testing', DB_DATABASE: 'careeratlas_e2e' }
    const windows = process.platform === 'win32'
    const cwd = process
        .cwd()
        .replace(/\\/g, '/')
        .replace(/^([A-Za-z]):/, (_, drive: string) => `/mnt/${drive.toLowerCase()}`)
    const distribution = process.env.E2E_WSL_DISTRO ? ['-d', process.env.E2E_WSL_DISTRO] : []
    const args = windows
        ? [
              ...distribution,
              '--cd',
              cwd,
              'env',
              'APP_ENV=testing',
              'DB_DATABASE=careeratlas_e2e',
              'php',
              'scripts/e2e-observe.php',
          ]
        : ['scripts/e2e-observe.php']
    return new Promise((resolve, reject) => {
        const child = spawn(windows ? 'wsl' : 'php', args, { env, windowsHide: true })
        let stdout = '',
            stderr = ''
        child.stdout.on('data', (data) => {
            stdout += data
        })
        child.stderr.on('data', (data) => {
            stderr += data
        })
        child.on('error', reject)
        child.on('close', (code) => {
            if (code !== 0)
                return reject(
                    new Error(
                        `Database observation failed (exit ${code}); check the PHP runtime and E2E_WSL_DISTRO. ${stderr}`,
                    ),
                )
            try {
                resolve(JSON.parse(stdout))
            } catch (error) {
                reject(error)
            }
        })
        child.stdin.end(JSON.stringify({ email: owner.email }))
    })
}
