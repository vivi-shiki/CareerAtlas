<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@/i18n'
import SourcePicker from './SourcePicker.vue'
import type { Experience, Selection } from '@/types/domain'
const props = defineProps<{ modelValue: Selection; records: Experience[]; disabled?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: Selection] }>()
const profile = [
    ['display_name', 'Display name'],
    ['direction', 'Career direction'],
    ['biography', 'Bio'],
    ['contact_links', 'Contact links'],
    ['preferred_language', 'Preferred language'],
]
const fields = [
    ['title', 'Experience title'],
    ['record_type', 'Record type'],
    ['occurrence', 'Occurrence date'],
    ['personal_action', 'My contribution'],
    ['result', 'Result'],
    ['validation', 'Validation'],
    ['remaining_work', 'Remaining work'],
    ['skills', 'Skills'],
    ['evidence', 'Evidence labels and links'],
]
const project = [
    ['title', 'Project title'],
    ['type', 'Project type'],
    ['role', 'Role'],
    ['background', 'Background'],
    ['team_outcome', 'Team outcome'],
    ['occurrence', 'Project dates'],
]
function patch(work: (value: Selection) => void) {
    const value = JSON.parse(JSON.stringify(props.modelValue)) as Selection
    work(value)
    emit('update:modelValue', value)
}
const ids = computed({
    get: () => props.modelValue.items.map((item) => item.experience_id),
    set: (ids: string[]) =>
        patch((value) => {
            value.items = ids.map(
                (id) =>
                    value.items.find((item) => item.experience_id === id) || {
                        experience_id: id,
                        fields: ['title', 'personal_action', 'skills', 'evidence'],
                        project_fields: ['title', 'role'],
                    },
            )
        }),
})
function toggle(
    group: 'profile_fields' | 'fields' | 'project_fields',
    key: string,
    index: number,
    checked: boolean,
) {
    patch((value) => {
        const target =
            group === 'profile_fields' ? value.profile_fields : value.items[index]![group]
        if (checked) target.push(key)
        else target.splice(target.indexOf(key), 1)
    })
}
function move(index: number, direction: number) {
    patch((value) => {
        const item = value.items.splice(index, 1)[0]!
        value.items.splice(index + direction, 0, item)
    })
}
</script>
<template>
    <fieldset :disabled="disabled">
        <h3>{{ t('Profile fields') }}</h3>
        <label v-for="[key, label] in profile" :key="key" class="p1-check"
            ><input
                type="checkbox"
                :checked="modelValue.profile_fields.includes(key!)"
                @change="
                    toggle('profile_fields', key!, 0, ($event.target as HTMLInputElement).checked)
                "
            />{{ t(label!) }}</label
        >
        <SourcePicker v-model="ids" :records="records" />
        <article
            v-for="(item, index) in modelValue.items"
            :key="item.experience_id"
            class="p1-source"
        >
            <h3>
                {{
                    records.find((record) => record.id === item.experience_id)?.title ||
                    t('Unavailable source')
                }}
            </h3>
            <button
                type="button"
                :disabled="index === 0"
                :aria-label="`${t('Move up')} ${index + 1}`"
                @click="move(index, -1)"
            >
                {{ t('Move up') }}</button
            ><button
                type="button"
                :disabled="index === modelValue.items.length - 1"
                :aria-label="`${t('Move down')} ${index + 1}`"
                @click="move(index, 1)"
            >
                {{ t('Move down') }}
            </button>
            <details>
                <summary>{{ t('Choose public fields') }}</summary>
                <h4>{{ t('Experience') }}</h4>
                <label v-for="[key, label] in fields" :key="key" class="p1-check"
                    ><input
                        type="checkbox"
                        :checked="item.fields.includes(key!)"
                        @change="
                            toggle(
                                'fields',
                                key!,
                                index,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />{{ t(label!) }}</label
                >
                <h4>{{ t('Project') }}</h4>
                <label v-for="[key, label] in project" :key="key" class="p1-check"
                    ><input
                        type="checkbox"
                        :checked="item.project_fields.includes(key!)"
                        @change="
                            toggle(
                                'project_fields',
                                key!,
                                index,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />{{ t(label!) }}</label
                >
            </details>
        </article>
    </fieldset>
</template>
