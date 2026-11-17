<script setup lang="ts">
import { t } from '@/i18n'
import { computed, ref, watch } from 'vue'
import { Input } from './ui/input'
import { Button } from './ui/button'
import type { Occurrence, Precision } from '@/types/domain'
const props = defineProps<{ modelValue: Occurrence | null; project?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: Occurrence | null] }>()
const ranged = ref(!!props.modelValue?.end || !!props.modelValue?.ongoing || !!props.project)
const value = computed(() => props.modelValue || { start: '', precision: 'month' as Precision })
function update(key: keyof Occurrence, field: string | boolean | null) {
    const next = { ...value.value, [key]: field }
    if (key === 'ongoing' && field) {
        next.end = null
        next.end_precision = null
    }
    emit('update:modelValue', next)
}
function range() {
    ranged.value = !ranged.value
    if (!ranged.value)
        emit('update:modelValue', { start: value.value.start, precision: value.value.precision })
}
watch(
    () => props.modelValue,
    (value) => {
        if (value?.end || value?.ongoing) ranged.value = true
    },
)
</script>
<template>
    <div class="occurrence-fields">
        <div class="date-fields">
            <div class="field">
                <label :for="project ? 'start-date' : 'date'"
                    >{{ project ? t('Start date') : t('Date') }}
                    <span v-if="!project" class="required" aria-hidden="true">*</span></label
                ><Input
                    :id="project ? 'start-date' : 'date'"
                    name="occurrence"
                    :aria-label="project ? t('Start date') : t('Date')"
                    :model-value="value.start"
                    :placeholder="
                        value.precision === 'year'
                            ? 'YYYY'
                            : value.precision === 'day'
                              ? 'YYYY-MM-DD'
                              : 'YYYY-MM'
                    "
                    @update:model-value="update('start', String($event))"
                />
            </div>
            <div class="field">
                <label :for="project ? 'start-precision' : 'precision'">{{
                    project ? t('Start precision') : t('Precision')
                }}</label
                ><select
                    :id="project ? 'start-precision' : 'precision'"
                    :value="value.precision"
                    @change="update('precision', ($event.target as HTMLSelectElement).value)"
                >
                    <option value="month">{{ t('Month') }}</option>
                    <option value="year">{{ t('Year') }}</option>
                    <option value="day">{{ t('Day') }}</option>
                </select>
            </div>
        </div>
        <Button v-if="!project" variant="link" type="button" class="range-toggle" @click="range">{{
            ranged ? t('Remove date range') : t('Add date range')
        }}</Button>
        <div v-if="ranged" class="range-fields">
            <label class="check-row"
                ><input
                    type="checkbox"
                    :checked="value.ongoing"
                    @change="update('ongoing', ($event.target as HTMLInputElement).checked)"
                />{{ t('Ongoing') }}</label
            >
            <div v-if="!value.ongoing" class="date-fields">
                <div class="field">
                    <label for="end-date">{{ t('End date') }}</label
                    ><Input
                        id="end-date"
                        :model-value="value.end || ''"
                        :placeholder="t('Optional end date')"
                        @update:model-value="update('end', String($event))"
                    />
                </div>
                <div class="field">
                    <label for="end-precision">{{ t('End precision') }}</label
                    ><select
                        id="end-precision"
                        :value="value.end_precision || value.precision"
                        @change="
                            update('end_precision', ($event.target as HTMLSelectElement).value)
                        "
                    >
                        <option value="month">{{ t('Month') }}</option>
                        <option value="year">{{ t('Year') }}</option>
                        <option value="day">{{ t('Day') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
