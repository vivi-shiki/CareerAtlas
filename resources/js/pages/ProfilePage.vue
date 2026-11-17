<script setup lang="ts">
import { t } from '@/i18n'
import { onMounted, reactive, ref } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'
import WorkspaceShell from '@/components/WorkspaceShell.vue'
import PageHeading from '@/components/PageHeading.vue'
import FormFeedback from '@/components/FormFeedback.vue'
import LoadState from '@/components/LoadState.vue'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import { op } from '@/lib/api'
import { currentUser } from '@/lib/session'
import { useAction, useDraftGuard } from '@/composables/useFormState'
import type { Profile, Contact } from '@/types/domain'
const form = reactive({
    display_name: '',
    direction: '',
    biography: '',
    preferred_language: 'en',
    contact_links: [] as Contact[],
})
const version = ref(1),
    loaded = ref(false)
const loading = useAction(),
    saving = useAction()
const { dirty, markSaved, discard } = useDraftGuard(() => form)
async function load() {
    const profile = await loading.run(() => op<Profile>('get_profile'))
    if (profile) {
        Object.assign(form, {
            display_name: profile.display_name,
            direction: profile.direction || '',
            biography: profile.biography || '',
            preferred_language: profile.preferred_language,
            contact_links: profile.contact_links,
        })
        version.value = profile.version
        markSaved()
        loaded.value = true
    }
}
async function save() {
    const result = await saving.run(
        () => op<Profile>('update_profile', { ...form, expected_version: version.value }),
        'Profile saved.',
    )
    if (result) {
        version.value = result.version
        markSaved()
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
    <WorkspaceShell active="Profile" :name="currentUser?.name"
        ><div class="workspace-page">
            <nav class="breadcrumbs" :aria-label="t('Breadcrumb')">
                <strong>{{ t('Profile') }}</strong>
            </nav>
            <PageHeading
                :title="t('Your profile')"
                :description="t('Introduce yourself. Choose what to share when you publish.')"
                :status="dirty ? t('Unsaved changes · Private') : t('Private')"
            /><LoadState :loading="loading.busy.value" :error="loading.error.value" @retry="load"
                ><form
                    v-if="loaded"
                    class="profile-form form-stack"
                    novalidate
                    @submit.prevent="save"
                >
                    <fieldset class="form-fields" :disabled="saving.busy.value">
                        <FormFeedback :error="saving.error.value" :message="saving.message.value"
                            ><Button
                                v-if="saving.error.value?.status === 409"
                                type="button"
                                variant="outline"
                                @click="reloadSaved"
                                >{{ t('Reload saved version') }}</Button
                            ></FormFeedback
                        >
                        <div class="field">
                            <label for="display-name"
                                >{{ t('Display name') }}
                                <span class="required" aria-hidden="true">*</span></label
                            ><Input
                                id="display-name"
                                v-model="form.display_name"
                                name="display_name"
                                :aria-label="t('Display name')"
                                :aria-invalid="!!saving.error.value?.fields.display_name"
                            />
                        </div>
                        <div class="field">
                            <label for="direction"
                                >{{ t('Career direction') }}
                                <span class="optional">{{ t('(optional)') }}</span></label
                            ><Input
                                id="direction"
                                v-model="form.direction"
                                name="direction"
                                :aria-label="t('Career direction')"
                            />
                        </div>
                        <div class="field">
                            <label for="biography"
                                >{{ t('Bio') }}
                                <span class="optional">{{ t('(optional)') }}</span></label
                            ><Textarea
                                id="biography"
                                v-model="form.biography"
                                name="biography"
                                :aria-label="t('Bio')"
                                :rows="4"
                            />
                        </div>
                        <div class="field">
                            <label for="language">{{ t('Preferred language') }}</label
                            ><select id="language" v-model="form.preferred_language">
                                <option value="en">{{ t('English') }}</option>
                                <option value="ja">日本語</option>
                                <option value="zh">中文</option>
                            </select>
                        </div>
                        <section class="contact-section">
                            <h2>{{ t('Contact links') }}</h2>
                            <p class="muted">
                                {{
                                    t(
                                        'Optional. Included only when you select them for publication.',
                                    )
                                }}
                            </p>
                            <div
                                v-for="(contact, index) in form.contact_links"
                                :key="index"
                                class="contact-row"
                            >
                                <div class="field">
                                    <label :for="`contact-label-${index}`">{{
                                        t('Contact label {number}', { number: index + 1 })
                                    }}</label
                                    ><Input
                                        :id="`contact-label-${index}`"
                                        v-model="contact.label"
                                        :name="`contact_links.${index}.label`"
                                    />
                                </div>
                                <div class="field">
                                    <label :for="`contact-url-${index}`">{{
                                        t('Contact URL {number}', { number: index + 1 })
                                    }}</label
                                    ><Input
                                        :id="`contact-url-${index}`"
                                        v-model="contact.url"
                                        :name="`contact_links.${index}.url`"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    :aria-label="
                                        t('Remove contact {number}', { number: index + 1 })
                                    "
                                    @click="form.contact_links.splice(index, 1)"
                                    ><Trash2
                                /></Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.contact_links.length >= 10"
                                @click="form.contact_links.push({ label: '', url: '' })"
                                ><Plus />{{ t('Add contact link') }}</Button
                            >
                        </section>
                        <div class="actions">
                            <Button type="submit" :disabled="saving.busy.value">{{
                                t('Save profile')
                            }}</Button>
                        </div>
                    </fieldset>
                </form></LoadState
            >
        </div></WorkspaceShell
    >
</template>
