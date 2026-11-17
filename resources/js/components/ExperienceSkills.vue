<script setup lang="ts">
import { t } from '@/i18n'
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import SkillChips from './SkillChips.vue'
import AppDialog from './AppDialog.vue'
import FormFeedback from './FormFeedback.vue'
import { Input } from './ui/input'
import { Button } from './ui/button'
import { useAction } from '@/composables/useFormState'
import { op } from '@/lib/api'
import type { Experience, SkillRef } from '@/types/domain'
const props = defineProps<{ record: Experience | null; prepare: () => Promise<Experience> }>()
const emit = defineEmits<{ saved: [value: Experience] }>()
const open = ref(false),
    name = ref(''),
    saving = useAction()
function add() {
    name.value = ''
    saving.error.value = null
    open.value = true
}
async function attach() {
    const result = await saving.run(async () => {
        const record = await props.prepare()
        const skill = await op<SkillRef>('create_skill', { name: name.value })
        return op<Experience>('attach_skill', {
            id: record.id,
            skill_id: skill.id,
            expected_version: record.version,
        })
    })
    if (result) {
        emit('saved', result)
        open.value = false
    }
}
async function remove(name: string) {
    const skill = props.record?.skills.find((skill) => skill.name === name)
    if (!skill) return
    const result = await saving.run(async () => {
        const record = await props.prepare()
        return op<Experience>('detach_skill', {
            id: record.id,
            skill_id: skill.id,
            expected_version: record.version,
        })
    })
    if (result) emit('saved', result)
}
</script>
<template>
    <div class="field">
        <label>{{ t('Skills') }}</label
        ><FormFeedback v-if="!open" :error="saving.error.value" /><SkillChips
            :skills="record?.skills.map((skill) => skill.name) || []"
            editable
            @remove="remove"
            ><Button
                type="button"
                variant="outline"
                class="add-skill"
                :disabled="!record || saving.busy.value"
                @click="add"
                ><Plus />{{ t('Add skill') }}</Button
            ></SkillChips
        ><small v-if="!record" class="muted">{{ t('Save a draft to connect skills.') }}</small
        ><AppDialog v-model:open="open" :title="t('Connect a skill')"
            ><form class="form-stack" novalidate @submit.prevent="attach">
                <fieldset class="form-fields" :disabled="saving.busy.value">
                    <FormFeedback :error="saving.error.value" />
                    <div class="field">
                        <label for="connect-skill-name">{{ t('Skill name') }}</label
                        ><Input
                            id="connect-skill-name"
                            v-model="name"
                            name="name"
                            :placeholder="t('Existing name, alias or a new skill')"
                        />
                    </div>
                    <div class="actions">
                        <Button type="button" variant="outline" @click="open = false">{{
                            t('Cancel')
                        }}</Button
                        ><Button type="submit" :disabled="saving.busy.value">{{
                            t('Connect skill')
                        }}</Button>
                    </div>
                </fieldset>
            </form></AppDialog
        >
    </div>
</template>
