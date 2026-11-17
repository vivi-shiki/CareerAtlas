<script setup lang="ts">
import { t, projectType } from '@/i18n'
import { occurrenceText } from '@/lib/portfolio'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { Plus } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import { Button } from '@/components/ui/button'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import { useAction } from '@/composables/useFormState'
import { confirmAction } from '@/composables/useConfirm'
import type { Project, Experience, PageResult } from '@/types/domain'
const route = useRoute(),
    router = useRouter(),
    id = route.params.id as string
const project = ref<Project | null>(null),
    experiences = ref<PageResult<Experience> | null>(null)
const loading = useAction(),
    saving = useAction(),
    archived = ref(false),
    page = ref(1)
async function load() {
    const result = await loading.run(async () => {
        const [project, experiences] = await Promise.all([
            op<Project>('get_project', { id }),
            op<PageResult<Experience>>('list_experiences', {
                project_id: id,
                archived: archived.value,
                page: page.value,
            }),
        ])
        return { project, experiences }
    })
    if (result) {
        project.value = result.project
        experiences.value = result.experiences
    }
}
async function lifecycle(action: 'archive' | 'restore' | 'delete') {
    if (!project.value) return
    if (
        action === 'delete' &&
        !(await confirmAction({
            title: 'Delete project?',
            message:
                'Dependent published experiences will become unavailable. Archiving keeps them readable.',
            confirm: 'Delete project',
            destructive: true,
        }))
    )
        return
    const result = await saving.run(
        () => op<Project>(`${action}_project`, { id, expected_version: project.value!.version }),
        action === 'restore' ? 'Project restored.' : 'Project archived.',
    )
    if (result) {
        if (action === 'delete') await router.push('/projects')
        else project.value = result
    }
}
function filter() {
    page.value = 1
    void load()
}
function nextPage(value: number) {
    page.value = value
    void load()
}
onMounted(load)
</script>
<template>
    <WorkspaceShell :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <RouterLink to="/projects">{{ t('Projects') }}</RouterLink
                ><span>/</span><strong>{{ t('Project') }}</strong>
            </nav>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><template v-if="project"
                    ><PageHeading
                        :title="project.title"
                        :description="`${projectType(project.type)} · ${project.role}`"
                        :status="project.archived_at ? t('Archived') : t('Private workspace')"
                    /><FormFeedback :error="saving.error.value" :message="saving.message.value" />
                    <div class="project-context">
                        <p v-if="project.background" class="prose-value">
                            {{ project.background }}
                        </p>
                        <p v-if="project.occurrence" class="muted">
                            {{ occurrenceText(project.occurrence) }}
                        </p>
                        <template v-if="project.team_outcome"
                            ><h3>{{ t('Team outcome') }}</h3>
                            <p class="prose-value">{{ project.team_outcome }}</p></template
                        >
                    </div>
                    <div class="detail-actions">
                        <Button variant="outline" @click="router.push(`/projects/${id}/edit`)">{{
                            t('Edit project')
                        }}</Button
                        ><Button
                            variant="outline"
                            :disabled="saving.busy.value"
                            @click="lifecycle(project.archived_at ? 'restore' : 'archive')"
                            >{{
                                project.archived_at ? t('Restore project') : t('Archive project')
                            }}</Button
                        ><Button
                            variant="ghost"
                            :disabled="saving.busy.value"
                            @click="lifecycle('delete')"
                            >{{ t('Delete project') }}</Button
                        >
                    </div>
                    <section class="project-experiences">
                        <header>
                            <h2>{{ t('Experiences') }}</h2>
                            <Button @click="router.push(`/projects/${id}/experiences/new`)"
                                ><Plus />{{ t('Add experience') }}</Button
                            >
                        </header>
                        <label class="check-row"
                            ><input v-model="archived" type="checkbox" @change="filter" />{{
                                t('Show archived experiences')
                            }}</label
                        >
                        <div v-if="!experiences?.total" class="empty-state">
                            <h3>
                                {{
                                    archived
                                        ? t('No archived experiences')
                                        : t('Capture your first contribution')
                                }}
                            </h3>
                            <p>
                                {{
                                    t(
                                        'A short draft is enough to begin. Confirmation and publication are separate steps.',
                                    )
                                }}
                            </p>
                        </div>
                        <ul v-else class="record-list">
                            <li v-for="experience in experiences.items" :key="experience.id">
                                <div>
                                    <RouterLink :to="`/experiences/${experience.id}`"
                                        ><h2>
                                            {{ experience.title || t('Untitled draft') }}
                                        </h2></RouterLink
                                    >
                                    <p>
                                        {{
                                            occurrenceText(experience.occurrence) ||
                                            t('Date not recorded')
                                        }}
                                        ·
                                        {{
                                            experience.status === t('confirmed')
                                                ? t('Confirmed')
                                                : t('Draft')
                                        }}
                                        {{ t('· Private') }}
                                    </p>
                                    <p v-if="experience.personal_action" class="record-summary">
                                        {{ experience.personal_action }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                        <nav
                            v-if="experiences && experiences.total > experiences.per_page"
                            class="pagination"
                            :aria-label="t('Experience pages')"
                        >
                            <Button
                                variant="outline"
                                :disabled="page <= 1"
                                @click="nextPage(page - 1)"
                                >{{ t('Previous page') }}</Button
                            ><span>{{ t('Page {page}', { page }) }}</span
                            ><Button
                                variant="outline"
                                :disabled="page * experiences.per_page >= experiences.total"
                                @click="nextPage(page + 1)"
                                >{{ t('Next page') }}</Button
                            >
                        </nav>
                    </section></template
                ></LoadState
            >
        </div></WorkspaceShell
    >
</template>
