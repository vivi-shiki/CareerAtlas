<script setup lang="ts">
import { t, skillCounts } from '@/i18n'
import { onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { X } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import LoadState from '@/components/LoadState.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import type { Skill } from '@/types/domain'
const id = useRoute().params.id as string
const skill = ref<Skill | null>(null),
    name = ref(''),
    alias = ref(''),
    loading = useAction(),
    saving = useAction()
const { markSaved } = useDraftGuard(() => ({ name: name.value, alias: alias.value }))
async function load() {
    const data = await loading.run(() => op<Skill>('get_skill', { id }))
    if (data) {
        skill.value = data
        name.value = data.name
        markSaved()
    }
}
async function mutate(operation: string, extra: Record<string, string>) {
    if (!skill.value) return
    const data = await saving.run(
        () => op<Skill>(operation, { id, expected_version: skill.value!.version, ...extra }),
        'Skill saved.',
    )
    if (data) {
        skill.value = data
        name.value = data.name
        alias.value = ''
        markSaved()
    }
}
onMounted(load)
</script>
<template>
    <WorkspaceShell active="Skills" :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <RouterLink to="/skills">{{ t('Skills') }}</RouterLink
                ><span>/</span><strong>{{ t('Skill detail') }}</strong>
            </nav>
            <LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><template v-if="skill"
                    ><PageHeading
                        :title="skill.name"
                        :description="skillCounts(skill.project_count, skill.experience_count)"
                    /><FormFeedback :error="saving.error.value" :message="saving.message.value" />
                    <div class="profile-form form-stack">
                        <form
                            class="inline-form"
                            novalidate
                            @submit.prevent="mutate('update_skill', { name })"
                        >
                            <div class="field">
                                <label for="skill-name">{{ t('Skill name') }}</label
                                ><Input id="skill-name" v-model="name" name="name" />
                            </div>
                            <Button type="submit" :disabled="saving.busy.value">{{
                                t('Rename skill')
                            }}</Button>
                        </form>
                        <section>
                            <h2>{{ t('Aliases') }}</h2>
                            <p class="muted">
                                {{
                                    t(
                                        'Different names connect to the same skill without duplicating counts.',
                                    )
                                }}
                            </p>
                            <div class="skill-chips alias-list">
                                <span v-for="item in skill.aliases" :key="item" class="skill-chip"
                                    >{{ item
                                    }}<button
                                        :aria-label="t('Remove alias {name}', { name: item })"
                                        :disabled="saving.busy.value"
                                        @click="mutate('delete_skill_alias', { alias: item })"
                                    >
                                        <X /></button
                                ></span>
                            </div>
                            <form
                                class="inline-form"
                                novalidate
                                @submit.prevent="mutate('add_skill_alias', { alias })"
                            >
                                <div class="field">
                                    <label for="alias">{{ t('New alias') }}</label
                                    ><Input id="alias" v-model="alias" name="alias" />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    :disabled="saving.busy.value"
                                    >{{ t('Add alias') }}</Button
                                >
                            </form>
                        </section>
                    </div>
                    <section class="project-experiences">
                        <h2>{{ t('Supporting experiences') }}</h2>
                        <p v-if="!skill.experiences.length" class="empty-inline">
                            {{ t('No experiences are connected to this skill yet.') }}
                        </p>
                        <ul v-else class="record-list">
                            <li v-for="experience in skill.experiences" :key="experience.id">
                                <div>
                                    <RouterLink :to="`/experiences/${experience.id}`"
                                        ><h3>
                                            {{ experience.title || t('Untitled draft') }}
                                        </h3></RouterLink
                                    >
                                    <p>
                                        {{
                                            experience.status === t('confirmed')
                                                ? t('Confirmed')
                                                : t('Draft')
                                        }}
                                        {{ t('· Private') }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </section></template
                ></LoadState
            >
        </div></WorkspaceShell
    >
</template>
