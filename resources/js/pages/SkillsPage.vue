<script setup lang="ts">
import { t, skillCounts } from '@/i18n'
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Search, Plus } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import AppDialog from '@/components/AppDialog.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useAction } from '@/composables/useFormState'
import { op, ApiError } from '@/lib/api'
import { currentUser } from '@/lib/session'
import type { Skill } from '@/types/domain'
const query = ref(''),
    skills = ref<Skill[]>([]),
    creating = ref(false),
    name = ref('')
const loading = useAction(),
    saving = useAction()
let revision = 0
async function load() {
    const run = ++revision
    loading.busy.value = true
    loading.error.value = null
    try {
        const result = await op<Skill[]>('list_skills', { query: query.value })
        if (run === revision) skills.value = result
    } catch (e) {
        if (run === revision) loading.error.value = e as ApiError
    } finally {
        if (run === revision) loading.busy.value = false
    }
}
function open() {
    name.value = ''
    saving.error.value = null
    creating.value = true
}
async function create() {
    const skill = await saving.run(() => op<Skill>('create_skill', { name: name.value }))
    if (skill) {
        creating.value = false
        query.value = ''
        await load()
    }
}
watch(query, load)
onMounted(load)
</script>
<template>
    <WorkspaceShell active="Skills" :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <strong>{{ t('Skills') }}</strong>
            </nav>
            <PageHeading
                :title="t('Skills connected to your work')"
                :description="
                    t('Follow each skill back to the experiences and evidence behind it.')
                "
            />
            <div class="list-toolbar">
                <div class="search-field">
                    <Search /><Input
                        v-model="query"
                        :aria-label="t('Search skills')"
                        :placeholder="t('Search skills or aliases')"
                    />
                </div>
                <Button @click="open"><Plus />{{ t('Add skill') }}</Button>
            </div>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><div v-if="!skills.length" class="empty-state">
                    <h2>{{ query ? t('No skills match your search.') : t('No skills yet') }}</h2>
                    <p>{{ t('Add a skill or connect one while recording an experience.') }}</p>
                    <Button v-if="query" variant="outline" @click="query = ''">{{
                        t('Clear search')
                    }}</Button>
                </div>
                <ul v-else class="record-list">
                    <li v-for="skill in skills" :key="skill.id">
                        <div>
                            <RouterLink :to="`/skills/${skill.id}`"
                                ><h2>{{ skill.name }}</h2></RouterLink
                            >
                            <p>
                                {{ skillCounts(skill.project_count, skill.experience_count) }}
                            </p>
                            <small v-if="skill.aliases?.length"
                                >{{ t('Also known as') }} {{ skill.aliases.join(', ') }}</small
                            >
                        </div>
                    </li>
                </ul></LoadState
            >
        </div>
        <AppDialog v-model:open="creating" :title="t('Add skill')"
            ><form class="form-stack" novalidate @submit.prevent="create">
                <FormFeedback :error="saving.error.value" />
                <div class="field">
                    <label for="skill-name">{{ t('Skill name') }}</label
                    ><Input id="skill-name" v-model="name" name="name" />
                </div>
                <div class="actions">
                    <Button variant="outline" type="button" @click="creating = false">{{
                        t('Cancel')
                    }}</Button
                    ><Button type="submit" :disabled="saving.busy.value">{{
                        t('Save skill')
                    }}</Button>
                </div>
            </form></AppDialog
        ></WorkspaceShell
    >
</template>
