import { ref } from 'vue'
import type { User } from '@/types/domain'
import { ApiError, request, resetCsrf } from './api'

export const currentUser = ref<User | null>(null)
let checked = false
export async function loadSession(force = false) {
    if (checked && !force) return currentUser.value
    try {
        currentUser.value = await request<User>('/api/auth/me')
    } catch (error) {
        if (!(error instanceof ApiError && error.status === 401)) throw error
        currentUser.value = null
    }
    checked = true
    return currentUser.value
}
export function setSession(user: User | null) {
    currentUser.value = user
    checked = true
    resetCsrf()
}
export async function signOut() {
    await request('/api/auth/logout', {})
    setSession(null)
}
