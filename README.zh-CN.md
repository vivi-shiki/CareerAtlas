# CareerAtlas

[English](README.md) · [Chinese](README.zh-CN.md) · [Japanese](README.ja.md)

**用 AI Agent 整理经历、准备面试，让职业故事有据可循。**

CareerAtlas 将项目、个人贡献、技能与证据连接起来，**让 AI Agent 帮你整理经历、准备面试，并根据来源回答作品集问题**。内置英语、简体中文与日语。

**技术栈：** PHP 8.3+（**Laravel 13 后端框架**）· Vue 3 · TypeScript · PostgreSQL。

[AI Agent 上手](docs/zh-CN/ai-agent.md) · [使用指南](docs/zh-CN/index.md) · [快速上手](docs/zh-CN/quickstart.md) · [开发指南](docs/zh-CN/development.md)

## 认识你的 AI Agent

- **整理与准备**：把笔记整理为可编辑的经历草稿，将要求对应到选定证据，起草回答并反馈文字练习。
- **展示与讲解**：起草机会简介、提供待审核的导出译文，让访客通过带有可点击来源引用的问答了解你的工作。
- **由你决定**：Agent 在选定来源范围内工作，由你审核、确认和发布；是否启用取决于运营配置，手动流程始终可用。

![AI Agent 根据来源回答作品集问题](docs/assets/screenshots/zh-CN/qa.jpg)

*实际应用界面，使用虚构数据与固定输出的测试提供方演示流程，不代表真实模型质量。[进入 AI Agent 使用教程](docs/zh-CN/ai-agent.md)。*

## 可以做什么

- **记录**：整理项目，区分个人行动与团队成果，关联技能和证据，保留修订历史。
- **采集**：保存笔记，检查可选 AI 草稿和重复候选，再自行确认生成的经历。
- **准备**：选择来源、起草与管理回答版本，进行文字练习并保留有用反馈。
- **分享**：预览选中字段，发布支持引用问答的作品集，并可撤销访问。
- **定制**：为机会创建独立页面，导出经过检查的 Markdown，也可选择翻译。

P0 与 P1 已实现。组织、指定接收人授权和他人确认仍在规划中。AI 不会替你确认或发布；没有模型提供方时仍可手动操作。[隐私与当前限制](docs/zh-CN/privacy-and-ai.md)。

## 如何使用

1. 在首页点击**登录**，需要时创建账号。
2. 添加项目和经历，关联证据并确认事实。
3. 跟随 [AI Agent 教程](docs/zh-CN/ai-agent.md)起草回答，或发布作品集并体验引用问答；分享前由你审核、确认草稿。

[图文指南](docs/zh-CN/index.md)涵盖完整流程、异常恢复和三语实际截图。

## 技术架构

| 层级 | 技术与用途 |
| --- | --- |
| 界面 | Vue 3、TypeScript、Vite、Vue I18n |
| 应用 | PHP 8.3+（Laravel 13 后端框架），共用 HTTP/MCP 操作与服务端权限 |
| 数据与 AI | PostgreSQL、版本化发布快照、数据库队列、有边界的模型适配器 |
| 文档 | VitePress，静态页面与本地搜索，随应用部署 |

[架构与代码导航](docs/zh-CN/architecture.md) · [API 与 MCP](docs/zh-CN/mcp.md)。准确依赖版本以锁文件为准。

## 本地运行

需要 PHP 8.3+、Composer 2、Node.js 22+ 和 PostgreSQL 16+。克隆仓库后，在根目录使用 POSIX shell 执行下列命令。先创建数据库和角色；只在首次设置时复制环境文件、生成应用密钥。

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
# Configure a PostgreSQL database and its credentials in .env first.
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

打开 `http://127.0.0.1:8000`，AI 默认关闭。[完整开发指南](docs/zh-CN/development.md)包含 PHP 扩展、Windows/WSL、队列、调度器和隔离测试库的设置。

## 开发与贡献

`npm run dev` 启动产品热更新，`npm run docs:dev` 预览文档，`npm run build` 同时构建两者。执行 `npm run test:i18n`、`npm run test:docs`，并在独立本地测试库上运行相关 PHP/浏览器测试。

先阅读 [AGENTS.md](AGENTS.md) 和[工程知识库](.github/KnowledgeBase/Index.md)。提交聚焦的拉取请求并附验证结果。密钥、真实经历及 `.local/` 不应进入 Git。[虚构案例库](examples/case-library/README.md)提供作品集示例与可选导入说明。
