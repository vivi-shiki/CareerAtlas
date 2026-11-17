import { locale } from '@/i18n'
export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public code = '',
        public fields: Record<string, string[]> = {},
    ) {
        super(message)
    }
}

let csrf: string | null =
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || null
export function resetCsrf() {
    csrf = null
}

async function csrfToken() {
    if (!csrf) {
        const response = await fetch('/api/csrf', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Accept-Language': locale.value },
        })
        if (!response.ok)
            throw new ApiError(response.status, 'Unable to refresh your session. Please retry.')
        csrf = (await response.json()).csrf_token
    }
    return csrf!
}

export async function request<T>(
    url: string,
    data?: unknown,
    signal?: AbortSignal,
    refresh = true,
): Promise<T> {
    try {
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Accept-Language': locale.value,
        }
        if (data !== undefined) {
            headers['Content-Type'] = 'application/json'
            headers['X-CSRF-TOKEN'] = await csrfToken()
        }
        const response = await fetch(url, {
            method: data === undefined ? 'GET' : 'POST',
            credentials: 'same-origin',
            headers,
            body: data === undefined ? undefined : JSON.stringify(data),
            signal,
        })
        if (response.status === 419 && refresh) {
            resetCsrf()
            return request<T>(url, data, signal, false)
        }
        const body = response.status === 204 ? null : await response.json().catch(() => ({}))
        if (!response.ok) {
            const message =
                response.status === 401
                    ? 'Your session has expired. Sign in to continue; your input is still here.'
                    : response.status === 404
                      ? 'Content unavailable'
                      : body?.message_key ||
                        body?.message ||
                        'Unable to complete this request. Please retry.'
            throw new ApiError(
                response.status,
                message,
                body?.code || '',
                body?.error_keys || body?.errors || {},
            )
        }
        return (body?.data ?? body) as T
    } catch (error) {
        if (
            error instanceof ApiError ||
            (error instanceof DOMException && error.name === 'AbortError')
        )
            throw error
        throw new ApiError(
            0,
            'The network connection was interrupted. Your input is preserved; please retry.',
        )
    }
}

export function op<T>(name: string, input: unknown = {}): Promise<T> {
    return request<T>(`/api/operations/${name}`, input)
}
