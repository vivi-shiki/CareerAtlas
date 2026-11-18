<script setup lang="ts">
import LanguageSelector from '@/components/LanguageSelector.vue'
import { t } from '@/i18n'
import {
    BookOpen,
    Folder,
    GraduationCap,
    UserRound,
    Megaphone,
    MoreHorizontal,
    Menu,
} from 'lucide-vue-next'
import { inject, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { signOut } from '@/lib/session'
import { Button } from './ui/button'
import { confirmAction } from '@/composables/useConfirm'
import { draftGuardKey } from '@/composables/useFormState'

withDefaults(defineProps<{ active?: string; name?: string; preview?: boolean }>(), {
    active: 'Projects',
    name: 'Alex Morgan',
    preview: false,
})
const open = ref(false)
const router = useRouter()
const signingOut = ref(false)
const draftGuard = inject(draftGuardKey, null)
async function logout() {
    if (draftGuard && !(await draftGuard.discard())) return
    signingOut.value = true
    try {
        await signOut()
        draftGuard?.markSaved()
        await router.push('/login')
    } catch {
        await confirmAction({
            title: 'Sign out failed',
            message: 'Your session could not be ended. Please try again.',
            confirm: 'OK',
            cancel: 'Close',
        })
    } finally {
        signingOut.value = false
    }
}
const navigation = [
    { label: 'Projects', icon: Folder, href: '/projects', preview: '/design/editor' },
    { label: 'Skills', icon: GraduationCap, href: '/skills', preview: '/design/skills' },
    { label: 'Profile', icon: UserRound, href: '/profile', preview: '/design/profile' },
    { label: 'Publish', icon: Megaphone, href: '/publish', preview: '/design/publication' },
    { label: 'Capture', icon: BookOpen, href: '/capture', preview: '/design/p1-capture' },
    {
        label: 'Preparation',
        icon: GraduationCap,
        href: '/preparation',
        preview: '/design/p1-preparation',
    },
    {
        label: 'Opportunities',
        icon: Folder,
        href: '/opportunities',
        preview: '/design/p1-opportunity',
    },
]
</script>

<template>
    <div class="workspace">
        <header class="mobile-header">
            <RouterLink :to="preview ? '/design/editor' : '/projects'" class="brand"
                ><BookOpen /><strong>CareerAtlas</strong></RouterLink
            >
            <LanguageSelector /><button
                class="icon-button"
                :aria-label="t('Toggle navigation')"
                :aria-expanded="open"
                @click="open = !open"
            >
                <Menu />
            </button>
        </header>
        <aside class="sidebar" :class="{ 'is-open': open }">
            <RouterLink :to="preview ? '/design/editor' : '/projects'" class="brand"
                ><BookOpen /><span
                    ><strong>CareerAtlas</strong><small>{{ t('Personal workspace') }}</small></span
                ></RouterLink
            >
            <nav :aria-label="t('Workspace')">
                <RouterLink
                    v-for="item in navigation"
                    :key="item.label"
                    :to="preview ? item.preview : item.href"
                    :aria-current="active === item.label ? 'page' : undefined"
                    @click="open = false"
                    ><component :is="item.icon" /><span>{{ t(item.label) }}</span></RouterLink
                >
            </nav>
            <LanguageSelector />
            <div class="workspace-user">
                <span class="avatar">{{
                    name
                        .split(' ')
                        .map((n) => n[0])
                        .slice(0, 2)
                        .join('')
                }}</span
                ><span>{{ name }}</span
                ><MoreHorizontal aria-hidden="true" />
            </div>
            <Button
                v-if="!preview"
                variant="ghost"
                class="sign-out"
                :disabled="signingOut"
                @click="logout"
                >{{ t('Sign out') }}</Button
            >
        </aside>
        <main id="main" class="workspace-main"><slot /></main>
    </div>
</template>
