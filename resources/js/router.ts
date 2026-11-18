import { createRouter, createWebHistory } from 'vue-router'
import { loadSession } from '@/lib/session'
import AuthPage from '@/pages/AuthPage.vue'
import ProjectsPage from '@/pages/ProjectsPage.vue'
import ProjectPage from '@/pages/ProjectPage.vue'
import ProjectFormPage from '@/pages/ProjectFormPage.vue'
import ProfilePage from '@/pages/ProfilePage.vue'
import ExperiencePage from '@/pages/ExperiencePage.vue'
import SkillsPage from '@/pages/SkillsPage.vue'
import SkillPage from '@/pages/SkillPage.vue'
import PublishPage from '@/pages/PublishPage.vue'
import PublicPage from '@/pages/PublicPage.vue'
import UnavailablePage from '@/pages/UnavailablePage.vue'

const owner = { owner: true }
export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: () => import('@/pages/HomePage.vue') },
        { path: '/login', component: AuthPage },
        { path: '/register', component: AuthPage },
        { path: '/profile', component: ProfilePage, meta: owner },
        { path: '/projects', component: ProjectsPage, meta: owner },
        { path: '/projects/new', component: ProjectFormPage, meta: owner },
        { path: '/projects/:id/edit', component: ProjectFormPage, meta: owner },
        { path: '/projects/:projectId/experiences/new', component: ExperiencePage, meta: owner },
        { path: '/projects/:id', component: ProjectPage, meta: owner },
        { path: '/experiences/:id', component: ExperiencePage, meta: owner },
        { path: '/skills', component: SkillsPage, meta: owner },
        { path: '/skills/:id', component: SkillPage, meta: owner },
        { path: '/publish', component: PublishPage, meta: owner },
        { path: '/capture/:id?', component: () => import('@/pages/CapturePage.vue'), meta: owner },
        {
            path: '/preparation/:id?',
            component: () => import('@/pages/PreparationPage.vue'),
            meta: owner,
        },
        {
            path: '/opportunities/:id?',
            component: () => import('@/pages/OpportunityPage.vue'),
            meta: owner,
        },
        { path: '/p/:slug', component: PublicPage },
        { path: '/design/:screen', component: () => import('@/pages/design/DesignApp.vue') },
        { path: '/:pathMatch(.*)*', component: UnavailablePage },
    ],
    scrollBehavior: (to, _from, savedPosition) =>
        savedPosition || (to.hash ? { el: to.hash } : { top: 0 }),
})
router.beforeEach(async (to) => {
    if (to.meta.owner) {
        try {
            if (!(await loadSession())) return '/login'
        } catch {
            return '/login?unavailable=1'
        }
    }
})
