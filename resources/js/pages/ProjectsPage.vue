<script setup lang="ts">
import { t, projectType } from '@/i18n'
import { onMounted, ref, watch } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { Plus, Search, Folder, ArrowRight } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useAction } from '@/composables/useFormState'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import type { PageResult, Project } from '@/types/domain'
const router = useRouter(),
    loading = useAction()
const query = ref(''),
    type = ref(''),
    status = ref('active'),
    page = ref(1)
const result = ref<PageResult<Project>>({ items: [], total: 0, page: 1, per_page: 20 })
let revision = 0
async function load() {
    const run = ++revision
    const input = {
        query: query.value,
        archived: status.value === 'archived',
        page: page.value,
        ...(type.value ? { type: type.value } : {}),
    }
    // Filters may change during a request; only the latest request may update the list.
    try {
        loading.busy.value = true
        loading.error.value = null
        const data = await op<PageResult<Project>>('list_projects', input)
        if (run === revision) result.value = data
    } catch (error) {
        if (run === revision) loading.error.value = error as import('@/lib/api').ApiError
    } finally {
        if (run === revision) loading.busy.value = false
    }
}
watch([query, type, status], () => {
    page.value = 1
    void load()
})
function changePage(number: number) {
    page.value = number
    void load()
}
function clear() {
    query.value = ''
    type.value = ''
    status.value = 'active'
}
onMounted(load)
</script>
<template>
    <WorkspaceShell :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <strong>{{ t('Projects') }}</strong>
            </nav>
            <PageHeading
                :title="t('Your projects')"
                :description="t('Keep project context, your contribution and evidence together.')"
            />
            <div class="list-toolbar">
                <div class="search-field">
                    <Search /><Input
                        v-model="query"
                        :aria-label="t('Search projects')"
                        :placeholder="t('Search projects')"
                    />
                </div>
                <select v-model="type" :aria-label="t('Project type')">
                    <option value="">{{ t('All project types') }}</option>
                    <option value="commercial">{{ t('Commercial') }}</option>
                    <option value="personal">{{ t('Personal') }}</option>
                    <option value="learning">{{ t('Learning') }}</option></select
                ><select v-model="status" :aria-label="t('Project status')">
                    <option value="active">{{ t('Active') }}</option>
                    <option value="archived">{{ t('Archived') }}</option></select
                ><Button @click="router.push('/projects/new')"
                    ><Plus />{{ t('Create project') }}</Button
                >
            </div>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><div v-if="!result.total" class="empty-state">
                    <template v-if="query || type || status === 'archived'"
                        ><h2>{{ t('No projects match your filters.') }}</h2>
                        <Button variant="outline" @click="clear">{{
                            t('Clear filters')
                        }}</Button></template
                    ><template v-else
                        ><Folder />
                        <h2>{{ t('Start with a project') }}</h2>
                        <p>
                            {{
                                t(
                                    'A personal or learning project is enough. You do not need a complete résumé.',
                                )
                            }}
                        </p>
                        <Button @click="router.push('/projects/new')">{{
                            t('Create your first project')
                        }}</Button></template
                    >
                </div>
                <ul v-else class="record-list">
                    <li v-for="project in result.items" :key="project.id">
                        <div>
                            <RouterLink :to="`/projects/${project.id}`"
                                ><h2>{{ project.title }}</h2></RouterLink
                            >
                            <p>{{ projectType(project.type) }} · {{ project.role }}</p>
                            <p v-if="project.background" class="record-summary">
                                {{ project.background }}
                            </p>
                            <small>{{
                                project.archived_at ? t('Archived') : t('Private workspace')
                            }}</small>
                        </div>
                        <RouterLink
                            :to="`/projects/${project.id}`"
                            :aria-label="t('Open {title}', { title: project.title })"
                            ><ArrowRight
                        /></RouterLink>
                    </li>
                </ul>
                <nav
                    v-if="result.total > result.per_page"
                    class="pagination"
                    :aria-label="t('Project pages')"
                >
                    <Button variant="outline" :disabled="page <= 1" @click="changePage(page - 1)">{{
                        t('Previous page')
                    }}</Button
                    ><span>{{
                        t('Page {page} of {total}', {
                            page,
                            total: Math.ceil(result.total / result.per_page),
                        })
                    }}</span
                    ><Button
                        variant="outline"
                        :disabled="page * result.per_page >= result.total"
                        @click="changePage(page + 1)"
                        >{{ t('Next page') }}</Button
                    >
                </nav></LoadState
            >
        </div></WorkspaceShell
    >
</template>
