<script setup lang="ts">
import { t } from '@/i18n'
import { nextTick, ref, watch, useId } from 'vue'
import {
    DialogRoot,
    DialogPortal,
    DialogOverlay,
    DialogContent,
    DialogTitle,
    DialogDescription,
    DialogClose,
} from 'reka-ui'
import { X } from 'lucide-vue-next'
const props = withDefaults(
    defineProps<{ open: boolean; title: string; description?: string; wide?: boolean }>(),
    { description: '' },
)
const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const content = ref<InstanceType<typeof DialogContent> | null>(null)
const descriptionId = useId()
let returnFocus: HTMLElement | null = null
watch(
    () => props.open,
    (open) => {
        if (open) returnFocus = document.activeElement as HTMLElement
    },
    { flush: 'sync', immediate: true },
)
async function focus(event: Event) {
    event.preventDefault()
    await nextTick()
    const root = content.value?.$el as HTMLElement | undefined
    root?.querySelector<HTMLElement>(
        '[data-initial-focus], select, input, textarea, button',
    )?.focus()
}
function restore(event: Event) {
    event.preventDefault()
    if (returnFocus?.isConnected) returnFocus.focus()
}
</script>
<template>
    <DialogRoot :open="open" @update:open="emit('update:open', $event)"
        ><DialogPortal
            ><DialogOverlay class="dialog-overlay" /><DialogContent
                ref="content"
                class="app-dialog"
                :class="{ 'dialog-wide': wide }"
                :aria-describedby="description ? descriptionId : undefined"
                @open-auto-focus="focus"
                @close-auto-focus="restore"
                @pointer-down-outside.prevent
            >
                <DialogTitle class="dialog-title">{{ title }}</DialogTitle
                ><DialogDescription
                    v-if="description"
                    :id="descriptionId"
                    class="dialog-description"
                    >{{ description }}</DialogDescription
                >
                <slot /><DialogClose
                    class="icon-button dialog-close"
                    :aria-label="t('Close dialog')"
                    ><X
                /></DialogClose> </DialogContent></DialogPortal
    ></DialogRoot>
</template>
