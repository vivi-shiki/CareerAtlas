import { shallowRef } from 'vue'

export interface Confirmation {
    title: string
    message: string
    confirm: string
    cancel: string
    destructive: boolean
}
export const confirmation = shallowRef<Confirmation | null>(null)
let resolveConfirmation: ((accepted: boolean) => void) | null = null

export function confirmAction(
    options: Partial<Confirmation> & Pick<Confirmation, 'title' | 'message'>,
) {
    if (resolveConfirmation) resolveConfirmation(false)
    confirmation.value = { confirm: 'Continue', cancel: 'Cancel', destructive: false, ...options }
    return new Promise<boolean>((resolve) => {
        resolveConfirmation = resolve
    })
}
export function settleConfirmation(accepted: boolean) {
    const resolve = resolveConfirmation
    resolveConfirmation = null
    confirmation.value = null
    resolve?.(accepted)
}
