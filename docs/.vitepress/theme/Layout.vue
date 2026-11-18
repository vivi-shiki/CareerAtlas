<script setup lang="ts">
import DefaultTheme from 'vitepress/theme'
import { useData, useRouter } from 'vitepress'
import { computed, onMounted, watch } from 'vue'
const { lang, theme } = useData()
const router = useRouter()
function changeLanguage(event: Event) {
    const locale = (event.target as HTMLSelectElement).value
    // The URL changes before a lazy chapter finishes loading on a slow connection.
    const chapter =
        window.location.pathname.match(/^\/docs\/(?:en|zh-CN|ja)\/([^/]+\.html)$/)?.[1] ||
        'index.html'
    router.go(`/docs/${locale}/${chapter}`)
}
const home = computed(() =>
    lang.value === 'ja' ? '製品ホーム' : lang.value === 'zh-CN' ? '产品首页' : 'Product home',
)
onMounted(() =>
    watch(
        lang,
        (value) => {
            if (['en', 'zh-CN', 'ja'].includes(value)) {
                try {
                    localStorage.setItem('careeratlas:locale', value)
                } catch {
                    /* Storage is optional. */
                }
            }
        },
        { immediate: true },
    ),
)
</script>
<template>
    <DefaultTheme.Layout>
        <template #nav-bar-content-before>
            <select
                class="docs-language"
                :value="lang"
                :aria-label="theme.langMenuLabel || 'Language'"
                @change="changeLanguage"
            >
                <option value="en">English</option>
                <option value="zh-CN">简体中文</option>
                <option value="ja">日本語</option>
            </select>
        </template>
        <template #sidebar-nav-before
            ><a class="product-home" href="/" target="_self">← {{ home }}</a></template
        >
        <template #doc-bottom
            ><a class="product-home bottom-home" href="/" target="_self">← {{ home }}</a></template
        >
    </DefaultTheme.Layout>
</template>
