<script setup lang="ts">
import { ref } from 'vue'
import { t, locale } from '@/i18n'
import { op } from '@/lib/api'
import { useAction } from '@/composables/useFormState'
import AppDialog from './AppDialog.vue'
import FormFeedback from './FormFeedback.vue'
import type { Source } from '@/types/p1'
const props = defineProps<{ id: string; kind: 'experience' | 'answer' }>()
interface Revision {
    id: string
    number: number
    created_at: string
    payload: Record<string, unknown>
    differences?: { field: string; before: unknown; after: unknown }[]
}
const open = ref(false),
    rows = ref<Revision[]>([]),
    action = useAction()
const labels: Record<string, string> = {
    personal_action: 'Personal action',
    remaining_work: 'Remaining work',
    record_type: 'Record type',
    private_notes: 'Private notes',
    occurrence: 'Date',
    title: 'Title',
    result: 'Result',
    validation: 'Validation',
    question: 'Question',
    answer: 'Prepared answer',
    state: 'Status',
    status: 'Status',
    skills: 'Skills',
    evidence: 'Evidence',
    archived_at: 'Archived',
    provenance: 'Source',
    access_epoch: 'Public access revision',
}
function display(value: unknown): string {
    return value == null
        ? '—'
        : typeof value === 'object'
          ? JSON.stringify(value, null, 2)
          : String(value)
}
function sources(value: unknown): Source[] {
    return Array.isArray(value) ? (value as Source[]) : []
}
async function load() {
    open.value = true
    const result = await action.run(() =>
        op<Revision[]>(
            props.kind === 'experience' ? 'get_experience_history' : 'get_answer_history',
            { id: props.id },
        ),
    )
    if (result) rows.value = result
}
</script>
<template>
    <button type="button" @click="load">
        {{ kind === 'experience' ? t('Experience history') : t('Answer history') }}
    </button>
    <AppDialog
        v-model:open="open"
        :title="kind === 'experience' ? t('Experience history') : t('Answer history')"
        wide
    >
        <FormFeedback :error="action.error.value" />
        <p v-if="action.busy.value">{{ t('Loading...') }}</p>
        <article v-for="row in rows" :key="row.id" class="p1-source">
            <h3>{{ t('Version') }} {{ row.number }}</h3>
            <time>{{ new Date(row.created_at).toLocaleString(locale) }}</time>
            <template v-if="kind === 'answer'"
                ><p>{{ row.payload.question }}</p>
                <p class="p1-history">{{ row.payload.answer }}</p>
                <details v-if="sources(row.payload.sources).length">
                    <summary>{{ t('Sources') }}</summary>
                    <blockquote
                        v-for="source in sources(row.payload.sources)"
                        :key="source.id"
                        class="p1-source"
                    >
                        <p>{{ source.content.title }}</p>
                        <p>{{ source.content.personal_action }}</p>
                        <small>{{ t('Source version') }}: {{ source.record_version_id }}</small>
                    </blockquote>
                </details></template
            >
            <dl v-else>
                <template v-for="change in row.differences" :key="change.field"
                    ><dt>{{ t(labels[change.field] || change.field) }}</dt>
                    <dd class="p1-history">
                        <del>{{ display(change.before) }}</del
                        ><br /><ins>{{ display(change.after) }}</ins>
                    </dd></template
                >
            </dl>
        </article></AppDialog
    >
</template>
