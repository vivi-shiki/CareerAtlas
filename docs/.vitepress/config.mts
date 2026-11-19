import { defineConfig } from 'vitepress'
import { fileURLToPath } from 'node:url'

const labels = {
    en: {
        lang: 'en',
        label: 'English',
        title: 'Documentation',
        agent: 'AI Agent walkthrough',
        guide: 'User guide',
        dev: 'Build & contribute',
        reference: 'Reference',
        search: 'Search docs',
        outline: 'On this page',
        menu: 'Menu',
        top: 'Back to top',
        previous: 'Previous page',
        next: 'Next page',
        pages: [
            'Overview',
            'Quick start',
            'Projects & experience',
            'Capture & history',
            'Interview preparation',
            'Publish & portfolio Q&A',
            'Opportunities & export',
            'Privacy & AI',
            'Architecture',
            'Local development',
            'API & MCP',
            'Troubleshooting',
            'Feature coverage & roadmap',
            'Laravel backend',
            'Data model & publication',
            'Testing & verification',
        ],
    },
    'zh-CN': {
        lang: 'zh-CN',
        label: '简体中文',
        title: '使用文档',
        agent: 'AI Agent 上手',
        guide: '使用指南',
        dev: '开发与贡献',
        reference: '参考',
        search: '搜索文档',
        outline: '本页目录',
        menu: '目录',
        top: '返回顶部',
        previous: '上一页',
        next: '下一页',
        pages: [
            '文档概览',
            '快速上手',
            '项目与经历',
            '素材采集与历史',
            '面试准备',
            '发布与作品集问答',
            '机会与导出',
            '隐私与 AI',
            '技术架构',
            '本地开发',
            'API 与 MCP',
            '常见问题',
            '功能全览与路线图',
            'Laravel 后端实现',
            '数据模型与发布流程',
            '测试与验证',
        ],
    },
    ja: {
        lang: 'ja',
        label: '日本語',
        title: 'ドキュメント',
        agent: 'AI Agent 入門',
        guide: '使い方',
        dev: '開発・コントリビュート',
        reference: 'リファレンス',
        search: 'ドキュメントを検索',
        outline: 'このページの内容',
        menu: 'メニュー',
        top: 'トップに戻る',
        previous: '前のページ',
        next: '次のページ',
        pages: [
            '概要',
            'クイックスタート',
            'プロジェクトと経験',
            'メモの整理と履歴',
            '面接準備',
            '公開とポートフォリオ Q&A',
            '応募機会とエクスポート',
            'プライバシーと AI',
            '技術構成',
            'ローカル開発',
            'API と MCP',
            'トラブルシューティング',
            '機能一覧とロードマップ',
            'Laravel バックエンド',
            'データモデルと公開フロー',
            'テストと検証',
        ],
    },
}
const pages = [
    'index',
    'quickstart',
    'records',
    'capture',
    'preparation',
    'publishing',
    'opportunities',
    'privacy-and-ai',
    'architecture',
    'development',
    'mcp',
    'troubleshooting',
    'roadmap',
    'backend',
    'data-model',
    'testing',
]
const locales = Object.fromEntries(
    Object.entries(labels).map(([locale, text]) => {
        const item = (index: number) => ({
            text: text.pages[index],
            link: `/${locale}/${pages[index]}.html`,
        })
        const agent = { text: text.agent, link: `/${locale}/ai-agent.html` }
        return [
            locale,
            {
                lang: text.lang,
                label: text.label,
                title: `CareerAtlas · ${text.title}`,
                link: `/${locale}/index.html`,
                themeConfig: {
                    siteTitle: 'CareerAtlas',
                    logoLink: `/docs/${locale}/index.html`,
                    i18nRouting: false,
                    nav: [
                        agent,
                        { text: text.guide, link: `/${locale}/quickstart.html` },
                        { text: text.dev, link: `/${locale}/development.html` },
                    ],
                    sidebar: [
                        {
                            text: text.guide,
                            items: [
                                item(0),
                                agent,
                                ...pages.slice(1, 8).map((_, i) => item(i + 1)),
                            ],
                        },
                        { text: text.dev, items: [8, 13, 14, 9, 15, 10].map(item) },
                        { text: text.reference, items: [11, 12].map(item) },
                    ],
                    outline: { label: text.outline, level: [2, 3] },
                    sidebarMenuLabel: text.menu,
                    returnToTopLabel: text.top,
                    docFooter: { prev: text.previous, next: text.next },
                    darkModeSwitchLabel:
                        locale === 'en' ? 'Appearance' : locale === 'ja' ? '表示テーマ' : '外观',
                    langMenuLabel: locale === 'en' ? 'Language' : locale === 'ja' ? '言語' : '语言',
                    skipToContentLabel:
                        locale === 'en'
                            ? 'Skip to content'
                            : locale === 'ja'
                              ? '本文へ移動'
                              : '跳到正文',
                    editLink: {
                        pattern: `https://github.com/vivi-shiki/CareerAtlas/edit/main/docs/:path`,
                        text:
                            locale === 'en'
                                ? 'Edit this page on GitHub'
                                : locale === 'ja'
                                  ? 'GitHub で編集する'
                                  : '在 GitHub 上编辑本页',
                    },
                },
            },
        ]
    }),
)

export default defineConfig({
    base: '/docs/',
    title: 'CareerAtlas',
    description: 'Record your work. Prepare with evidence. Share selected experience.',
    outDir: fileURLToPath(new URL('../../public/docs', import.meta.url)),
    cleanUrls: false,
    locales,
    head: [['meta', { name: 'theme-color', content: '#fafaf9' }]],
    themeConfig: {
        socialLinks: [{ icon: 'github', link: 'https://github.com/vivi-shiki/CareerAtlas' }],
        search: {
            provider: 'local',
            options: {
                locales: {
                    'zh-CN': {
                        translations: {
                            button: { buttonText: '搜索文档', buttonAriaLabel: '搜索文档' },
                            modal: {
                                noResultsText: '没有找到相关结果',
                                resetButtonTitle: '清除搜索',
                                backButtonTitle: '返回',
                                displayDetails: '显示详细列表',
                                footer: {
                                    selectText: '选择',
                                    navigateText: '切换',
                                    closeText: '关闭',
                                },
                            },
                        },
                    },
                    ja: {
                        translations: {
                            button: { buttonText: '検索', buttonAriaLabel: 'ドキュメントを検索' },
                            modal: {
                                noResultsText: '該当する結果がありません',
                                resetButtonTitle: '検索をクリア',
                                backButtonTitle: '戻る',
                                displayDetails: '詳細を表示',
                                footer: {
                                    selectText: '選択',
                                    navigateText: '移動',
                                    closeText: '閉じる',
                                },
                            },
                        },
                    },
                },
            },
        },
    },
})
