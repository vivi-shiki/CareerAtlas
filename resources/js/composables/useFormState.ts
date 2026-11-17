import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    provide,
    ref,
    shallowRef,
    type InjectionKey,
} from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { ApiError } from '@/lib/api'
import { confirmAction } from './useConfirm'

export const draftGuardKey: InjectionKey<{
    discard: () => Promise<boolean>
    markSaved: () => void
}> = Symbol('draftGuard')

export function useAction() {
    const busy = ref(false),
        message = ref(''),
        error = shallowRef<ApiError | null>(null)
    async function run<T>(work: () => Promise<T>, success = ''): Promise<T | null> {
        if (busy.value) return null
        busy.value = true
        error.value = null
        message.value = ''
        try {
            const result = await work()
            message.value = success
            return result
        } catch (e) {
            error.value =
                e instanceof ApiError
                    ? e
                    : new ApiError(0, 'Unable to complete this request. Please retry.')
            busy.value = false
            await nextTick()
            const first = Object.keys(error.value.fields)[0]
            if (first) document.querySelector<HTMLElement>(`[name="${CSS.escape(first)}"]`)?.focus()
            return null
        } finally {
            busy.value = false
        }
    }
    return { busy, message, error, run }
}

export function useDraftGuard(value: () => unknown) {
    const saved = ref(JSON.stringify(value()))
    const dirty = computed(() => JSON.stringify(value()) !== saved.value)
    function markSaved() {
        saved.value = JSON.stringify(value())
    }
    async function discard() {
        if (!dirty.value) return true
        return confirmAction({
            title: 'Discard unsaved changes?',
            message: 'Your changes have not been saved.',
            confirm: 'Discard changes',
            cancel: 'Keep editing',
            destructive: true,
        })
    }
    onBeforeRouteLeave(() => discard())
    provide(draftGuardKey, { discard, markSaved })
    function beforeUnload(event: BeforeUnloadEvent) {
        if (dirty.value) {
            event.preventDefault()
            event.returnValue = ''
        }
    }
    onMounted(() => window.addEventListener('beforeunload', beforeUnload))
    onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
    return { dirty, markSaved, discard }
}
