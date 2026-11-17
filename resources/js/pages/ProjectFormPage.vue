<script setup lang="ts">
import { t } from '@/i18n'
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import OccurrenceFields from '@/components/OccurrenceFields.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import type { Project, Occurrence } from '@/types/domain'
const route = useRoute(),
    router = useRouter(),
    id = route.params.id as string | undefined
const form = reactive({
    title: '',
    type: 'personal',
    role: '',
    background: '',
    team_outcome: '',
    occurrence: null as Occurrence | null,
})
const version = ref(1),
    ready = ref(!id),
    loading = useAction(),
    saving = useAction()
const { markSaved, discard } = useDraftGuard(() => form)
async function load() {
    if (!id) return
    const project = await loading.run(() => op<Project>('get_project', { id }))
    if (project) {
        Object.assign(form, {
            title: project.title,
            type: project.type,
            role: project.role,
            background: project.background || '',
            team_outcome: project.team_outcome || '',
            occurrence: project.occurrence,
        })
        version.value = project.version
        markSaved()
        ready.value = true
    }
}
async function save() {
    const input = {
        ...form,
        occurrence: form.occurrence?.start ? form.occurrence : null,
        ...(id ? { id, expected_version: version.value } : {}),
    }
    const result = await saving.run(
        () => op<Project>(id ? 'update_project' : 'create_project', input),
        'Project saved.',
    )
    if (result) {
        markSaved()
        await router.replace(`/projects/${result.id}`)
    }
}
async function reloadSaved() {
    if (await discard()) {
        saving.error.value = null
        await load()
    }
}
onMounted(load)
</script>
<template>
    <WorkspaceShell :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <RouterLink to="/projects">{{ t('Projects') }}</RouterLink
                ><span>/</span><strong>{{ id ? t('Edit project') : t('Create project') }}</strong>
            </nav>
            <PageHeading
                :title="id ? t('Edit project context') : t('Start with a project')"
                :description="
                    t('A project connects your role, contributions and supporting evidence.')
                "
                :status="t('Private')"
            /><LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><form
                    v-if="ready"
                    class="profile-form form-stack"
                    novalidate
                    @submit.prevent="save"
                >
                    <fieldset class="form-fields" :disabled="saving.busy.value">
                        <FormFeedback :error="saving.error.value"
                            ><Button
                                v-if="saving.error.value?.status === 409"
                                type="button"
                                variant="outline"
                                @click="reloadSaved"
                                >{{ t('Reload saved version') }}</Button
                            ></FormFeedback
                        >
                        <div class="field">
                            <label for="title"
                                >{{ t('Title') }}
                                <span class="required" aria-hidden="true">*</span></label
                            ><Input
                                id="title"
                                v-model="form.title"
                                name="title"
                                :aria-label="t('Title')"
                                :aria-invalid="!!saving.error.value?.fields.title"
                            />
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label for="project-type"
                                    >{{ t('Project type') }}
                                    <span class="required" aria-hidden="true">*</span></label
                                ><select
                                    id="project-type"
                                    v-model="form.type"
                                    name="type"
                                    :aria-label="t('Project type')"
                                >
                                    <option value="commercial">{{ t('Commercial') }}</option>
                                    <option value="personal">{{ t('Personal') }}</option>
                                    <option value="learning">{{ t('Learning') }}</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="role"
                                    >{{ t('Role') }}
                                    <span class="required" aria-hidden="true">*</span></label
                                ><Input
                                    id="role"
                                    v-model="form.role"
                                    name="role"
                                    :aria-label="t('Role')"
                                    :aria-invalid="!!saving.error.value?.fields.role"
                                />
                            </div>
                        </div>
                        <OccurrenceFields v-model="form.occurrence" project />
                        <div class="field">
                            <label for="background"
                                >{{ t('Background') }}
                                <span class="optional">{{ t('(optional)') }}</span></label
                            ><Textarea
                                id="background"
                                v-model="form.background"
                                name="background"
                                :aria-label="t('Background')"
                                :rows="4"
                            />
                        </div>
                        <div class="field">
                            <label for="team-outcome"
                                >{{ t('Team outcome') }}
                                <span class="optional">{{ t('(optional)') }}</span></label
                            ><Textarea
                                id="team-outcome"
                                v-model="form.team_outcome"
                                name="team_outcome"
                                :aria-label="t('Team outcome')"
                                :rows="3"
                            /><small class="muted">{{
                                t(
                                    'This is shared project context. Record your own actions in an experience.',
                                )
                            }}</small>
                        </div>
                        <div class="actions">
                            <Button variant="outline" type="button" @click="router.back()">{{
                                t('Cancel')
                            }}</Button
                            ><Button type="submit" :disabled="saving.busy.value">{{
                                id ? t('Save project') : t('Create project')
                            }}</Button>
                        </div>
                    </fieldset>
                </form></LoadState
            >
        </div></WorkspaceShell
    >
</template>
