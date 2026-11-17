<script setup lang="ts">
import LanguageSelector from '@/components/LanguageSelector.vue'
import { t } from '@/i18n'
import { computed, reactive } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { BookOpen, Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import FormFeedback from '@/components/FormFeedback.vue'
import { request } from '@/lib/api'
import { setSession } from '@/lib/session'
import { useAction } from '@/composables/useFormState'
import type { User } from '@/types/domain'
const route = useRoute(),
    router = useRouter()
const registering = computed(() => route.path === '/register')
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const { busy, error, run } = useAction()
async function submit() {
    const payload = registering.value ? form : { email: form.email, password: form.password }
    const user = await run(() =>
        request<User>(registering.value ? '/api/auth/register' : '/api/auth/login', payload),
    )
    if (user) {
        setSession(user)
        form.password = ''
        form.password_confirmation = ''
        await router.replace('/projects')
    }
}
</script>
<template>
    <div class="auth-layout">
        <div class="auth-header">
            <RouterLink to="/" class="brand"><BookOpen /><strong>CareerAtlas</strong></RouterLink
            ><LanguageSelector />
        </div>
        <main id="main" class="auth-form">
            <h1>{{ registering ? t('Start your career atlas.') : t('Your work, connected.') }}</h1>
            <p class="muted">
                {{
                    registering
                        ? t('Create a private workspace for your experience.')
                        : t('Sign in to your personal workspace.')
                }}
            </p>
            <form class="form-stack" novalidate @submit.prevent="submit">
                <FormFeedback :error="error" />
                <div v-if="registering" class="field">
                    <label for="name">{{ t('Display name') }}</label
                    ><Input
                        id="name"
                        v-model="form.name"
                        name="name"
                        autocomplete="name"
                        :aria-invalid="!!error?.fields.name"
                    />
                </div>
                <div class="field">
                    <label for="email">{{ t('Email') }}</label
                    ><Input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        :aria-invalid="!!error?.fields.email"
                    />
                </div>
                <div class="field">
                    <label for="password">{{ t('Password') }}</label
                    ><Input
                        id="password"
                        v-model="form.password"
                        name="password"
                        type="password"
                        :autocomplete="registering ? 'new-password' : 'current-password'"
                        :aria-invalid="!!error?.fields.password"
                    /><small v-if="registering" class="muted">{{
                        t('Use at least 12 characters.')
                    }}</small>
                </div>
                <div v-if="registering" class="field">
                    <label for="password-confirmation">{{ t('Confirm password') }}</label
                    ><Input
                        id="password-confirmation"
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </div>
                <Button type="submit" :disabled="busy">{{
                    busy ? t('Please wait…') : registering ? t('Create account') : t('Sign in')
                }}</Button
                ><RouterLink :to="registering ? '/login' : '/register'" class="auth-switch">{{
                    registering ? t('Already have an account? Sign in') : t('Create an account')
                }}</RouterLink>
            </form>
            <p class="info-line">
                <Info />{{ t('Your records stay private until you choose to publish.') }}
            </p>
        </main>
    </div>
</template>
