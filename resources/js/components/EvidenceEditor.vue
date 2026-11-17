<script setup lang="ts">
import { t } from '@/i18n'
import { computed, reactive, ref } from 'vue'
import { Info, Plus } from 'lucide-vue-next'
import EvidenceList from './EvidenceList.vue'
import AppDialog from './AppDialog.vue'
import FormFeedback from './FormFeedback.vue'
import { Input } from './ui/input'
import { Textarea } from './ui/textarea'
import { Button } from './ui/button'
import { useAction } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import { op } from '@/lib/api'
import type { Experience, Evidence } from '@/types/domain'
import type { EvidenceView } from '@/types/portfolio'
const props = defineProps<{ record: Experience | null; prepare: () => Promise<Experience> }>()
const emit = defineEmits<{ saved: [value: Experience] }>()
const open = ref(false)
const editing = ref<Evidence | null>(null)
const { busy, error, run } = useAction()
const form = ref({ kind: 'repository', label: '', url: '', excerpt: '' })
let original = ''
const evidence = computed<EvidenceView[]>(() =>
    (props.record?.evidence || []).map((item) => ({
        id: item.id,
        label: item.label,
        provenance: item.provenance === 'owner_statement' ? 'Owner statement' : 'Supplied link',
        ...(item.url ? { url: item.url } : {}),
        ...(item.excerpt ? { statement: item.excerpt } : {}),
    })),
)
function edit(item?: EvidenceView) {
    editing.value = item ? props.record?.evidence.find((e) => e.id === item.id) || null : null
    Object.assign(form.value, {
        kind: editing.value?.kind || 'repository',
        label: editing.value?.label || '',
        url: editing.value?.url || '',
        excerpt: editing.value?.excerpt || '',
    })
    original = JSON.stringify(form.value)
    error.value = null
    open.value = true
}
async function close(value: boolean) {
    if (value || busy.value) return
    if (
        original !== JSON.stringify(form.value) &&
        !(await confirmAction({
            title: 'Discard unsaved changes?',
            message: 'This evidence has not been saved.',
            confirm: 'Discard changes',
            cancel: 'Keep editing',
            destructive: true,
        }))
    )
        return
    open.value = false
}
async function save() {
    const result = await run(async () => {
        const record = await props.prepare()
        return op<Experience>(editing.value ? 'update_evidence' : 'create_evidence', {
            ...(editing.value ? { id: editing.value.id } : { experience_id: record.id }),
            expected_version: record.version,
            kind: form.value.kind,
            label: form.value.label,
            url: form.value.kind === 'statement' ? null : form.value.url,
            excerpt: form.value.excerpt || null,
        })
    })
    if (result) {
        emit('saved', result)
        open.value = false
    }
}
async function remove() {
    if (
        !editing.value ||
        !(await confirmAction({
            title: 'Delete evidence?',
            message:
                'The current experience will become a draft. Existing published snapshots remain unchanged until you publish again.',
            confirm: 'Delete evidence',
            destructive: true,
        }))
    )
        return
    const result = await run(async () => {
        const record = await props.prepare()
        return op<Experience>('delete_evidence', {
            id: editing.value!.id,
            expected_version: record.version,
        })
    })
    if (result) {
        emit('saved', result)
        open.value = false
    }
}
</script>
<template>
    <aside class="evidence-rail">
        <h2>{{ t('Supporting evidence') }}</h2>
        <EvidenceList :evidence="evidence" editable @edit="edit" />
        <p class="info-line"><Info />{{ t('Links have not been independently verified.') }}</p>
        <Button variant="outline" class="evidence-add" :disabled="!record" @click="edit()">
            <Plus />{{ t('Add evidence') }}</Button
        >
        <p v-if="!record" class="empty-inline">
            {{ t('Save a draft to connect supporting evidence.') }}
        </p>
        <p class="privacy-notice">
            <Info />
            <span
                >{{ t('Confirmation keeps this record private.') }}<br />{{
                    t('Publishing is a separate step.')
                }}</span
            >
        </p>
        <AppDialog
            :open="open"
            :title="editing ? t('Edit evidence') : t('Add evidence')"
            @update:open="close"
        >
            <form class="form-stack" novalidate @submit.prevent="save">
                <fieldset class="form-fields" :disabled="busy">
                    <FormFeedback :error="error" />
                    <div class="field">
                        <label for="evidence-kind">{{ t('Evidence type') }}</label>
                        <select
                            id="evidence-kind"
                            v-model="form.kind"
                            name="kind"
                            data-initial-focus
                        >
                            <option value="repository">
                                {{ t('Repository or pull request') }}
                            </option>
                            <option value="demo">{{ t('Demo') }}</option>
                            <option value="document">{{ t('Document') }}</option>
                            <option value="statement">{{ t('Owner statement') }}</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="evidence-label">{{ t('Label') }}</label>
                        <Input id="evidence-label" v-model="form.label" name="label" />
                    </div>
                    <div v-if="form.kind !== 'statement'" class="field">
                        <label for="evidence-url">{{ t('URL') }}</label>
                        <Input
                            id="evidence-url"
                            v-model="form.url"
                            name="url"
                            placeholder="https://"
                        />
                        <p class="info-line">
                            <Info />{{
                                t(
                                    'This link will be stored, not fetched or independently verified.',
                                )
                            }}
                        </p>
                    </div>
                    <div class="field">
                        <label for="evidence-excerpt">{{
                            form.kind === 'statement' ? t('Statement') : t('Notes (optional)')
                        }}</label>
                        <Textarea
                            id="evidence-excerpt"
                            v-model="form.excerpt"
                            name="excerpt"
                            :rows="4"
                        />
                    </div>
                    <div class="actions">
                        <Button
                            v-if="editing"
                            type="button"
                            variant="ghost"
                            :disabled="busy"
                            @click="remove"
                            >{{ t('Delete evidence') }}</Button
                        >
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="busy"
                            @click="close(false)"
                            >{{ t('Cancel') }}</Button
                        >
                        <Button type="submit" :disabled="busy">{{ t('Save evidence') }}</Button>
                    </div>
                </fieldset>
            </form>
        </AppDialog>
    </aside>
</template>
