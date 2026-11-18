# 技术架构

CareerAtlas 是一个 Laravel 应用，包含 Vue 工作区、公开作品集与静态构建的文档站。

```text
Vue 3 / TypeScript 界面       已认证 MCP 客户端
             │                      │
             └── Laravel 业务操作 ───┘
                     │       │
              PostgreSQL   数据库队列 → 模型适配器
                     │
             选定版本的发布快照 → 公开页面 / 问答

VitePress → public/docs → 同域静态文档
```

## 组成部分

| 层级 | 职责 |
| --- | --- |
| Laravel 13 / PHP 8.3+ | 会话、权限、校验、事务、版本检查与公开内容投影 |
| Vue 3 / TypeScript / Vite | 工作区表单、来源预览、弹窗与访客问答 |
| Vue I18n | 英语、简体中文、日语界面词典 |
| PostgreSQL | 个人经历、不可变版本、发布快照与队列状态 |
| 模型提供方适配器 | 有边界的异步本人辅助及页面范围内的公开问答 |
| VitePress | 预渲染指南、本地搜索与语言导航，无需文档数据库 |

依赖的准确版本以锁文件为准。HTTP 操作与开发者 MCP 共用业务服务和输入模式。公开问答使用独立、受限的接口，不能任意调用本人修改操作。

## 数据流

本人保存草稿、确认来源版本、选择公开字段、核对预览后发布快照。乐观版本检查会拒绝过期写入。发布、读取来源和应用 AI 建议时会再次检查当前权限。编辑来源不会悄悄改写已发布快照。

队列进程处理 `agent` 队列，调度器清理过期练习与建议。手动工作流不依赖模型可用性。文档构建到 `public/docs`，随应用版本一起部署。

## 代码导航

| 路径 | 内容 |
| --- | --- |
| `app/Domain`、`app/Agent`、`app/Mcp` | 业务行为、模型集成、开发者工具 |
| `contracts`、`routes`、`database` | 操作模式、入口和数据库迁移 |
| `resources/js`、`resources/css` | 产品界面、翻译和样式 |
| `docs` | 三语读者文档与虚构截图 |
| `tests`、`scripts`、`deploy` | 验证、开发辅助和发布工具 |

深入阅读：[架构约定](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Architecture.md)、[权限约定](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Authorization.md)、[API 清单](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md)。
