# CareerAtlas

[English](README.md) | [简体中文](README.zh-CN.md) | [日本語](README.ja.md)

**一个由 AI 支持的职业作品集与技能知识系统，将个人经历、项目演示、贡献和证据相互关联，并通过对话检索已获授权的记录。**

CareerAtlas 是面向个人与组织的独立产品，帮助用户记录工作、展示能力并为新的机会做准备。未来的组织功能将支持员工分享经验和开展职业发展交流。这套文档定义产品、架构、交付路线和工程流程。

## 项目状态

P0 提供个人工作区、公开作品集、开发者 MCP 工具，以及有范围限制、附来源引用的公开问答助手。P1 增加私密记录整理、准备与练习、独立机会页面及需要审核的 AI 辅助。本地验收见 [P0 日志](.github/TaskLogs/2026-11-18-p0-implementation.md)和 [P1 日志](.github/TaskLogs/2026-11-18-p1-implementation.md)。P2 组织功能仍在规划中；此状态不代表已部署到云端。

## P1 使用流程

- **记录整理：**在项目中保存笔记，审核 AI 草稿与可选追问，检查重复候选，再新建或合并为私密经历。确认经历是独立步骤；经历历史展示修订与差异。
- **准备与练习：**选择已确认来源，记录需求，指定目标语言及简短或标准答案长度，审核有来源的建议并保留答案版本。文字练习支持逐题作答、重试、停止、可选反馈和明确保存。
- **机会页面：**维护私密概要，选择公开字段并排序，审核简介后发布独立页面。更新或撤销一个页面不会改变其他页面。可将获授权的快照导出为 Markdown；翻译字段需审核后再下载。

AI 不会确认或发布草稿。未知事实保留为信息缺口，来源变化会使待应用建议失效。个人 AI 草稿需要环境中单独设置授权名单；AI 关闭、失败或取消时仍可手动编辑。未保存练习和 AI 运行记录一天后过期，明确保留的练习和已应用草稿会继续保存。下载后的导出文件无法随之后的撤销而收回。

## 技术方向

- PHP / Laravel：身份认证、权限、校验、业务逻辑、API 和 Agent 工具。
- Vue 3 / TypeScript：内容编辑、作品集展示与对话界面。
- Vue I18n：英语、简体中文和日语界面文案。
- PostgreSQL：结构化职业记录、发布快照与引用关系。
- 模型服务适配器：通过服务端集成实现受限工具调用与结构化回答。
- 部署：可配置的应用运行环境、持久化 PostgreSQL、HTTPS 和可恢复的发布流程。

锁文件记录具体依赖版本。[构建与验证指南](.github/Guidelines/Building.md)说明数据库、前端资源、Web/队列进程和测试的配置。具体托管服务、模型订阅与环境设置属于本地运维文档。另见[部署契约](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md)。

## 公开文档与本地文档

Git 跟踪的根目录文档、`.github/` 和 `design/` 是面向 GitHub 的共享文档，包含需求、技术选型、架构、实现契约、工程标准、可复用开发任务及 UI/UX 产品设计参考。

`.local/docs/` 是被 Git 忽略的具体演示环境文档，记录实际云/API 服务、环境限制、额度、凭据位置和部署排障。操作该环境前先阅读其中的 `README.md`。新克隆的仓库不会包含它，共享文档必须能独立使用。两类文档都不应包含密钥值。归属规则见[文档规范](.github/Guidelines/Documentation.md)。

## 语言支持

登录页、工作区和公开作品集均可切换英语、简体中文与日语。界面优先使用浏览器内保存的偏好，其次使用浏览器语言，最后回退为英语。切换语言会保留未保存的表单输入和对话记录。个人档案中的偏好语言与界面语言相互独立。

导航、表单、确认弹窗、校验与恢复提示、助手控件、无障碍标签、日期及数量均支持本地化。用户填写的记录、来源摘录和已有 AI 回答保留原文。公开问答遵循问题的语言；个人草稿和导出使用所选的内容语言。翻译不会扩大访问权限，也不会更改发布快照。

三份 README 提供相同的产品概览，工程文档仍以英语维护。文案目录、API 行为、维护与验证规则见[本地化契约](.github/KnowledgeBase/KB_CareerAtlas_Localization.md)。

## 从这里开始

- [项目目标与范围](Project.md)
- [AI Agent 入口](AGENTS.md)
- [仓库工作指示](.github/copilot-instructions.md)
- [知识库与功能规格](.github/KnowledgeBase/Index.md)
- [实施任务列表](.github/TaskLogs/Copilot_Scrum.md)
- [文档来源](.github/KnowledgeBase/Sources.md)

## 产品设计参考

UI/UX 设计包括私密贡献编辑器、明确的发布预览，以及带来源引用问答的公开作品集。这些原创虚构数据设计图是实现页面的视觉参考，不是已部署服务的截图。

![公开作品集与带来源引用的问答设计](design/2026-11-17/mockups/03-public-portfolio.png)

- [设计包：三份 UI 设计与四条 UX 流程](design/2026-11-17/README.md)
- [UI 框架、页面规格与需求映射](.github/KnowledgeBase/KB_CareerAtlas_Design_UIUX.md)
- [页面状态与交互需求](.github/KnowledgeBase/KB_CareerAtlas_Design_UserExperience.md)

克隆后可打开静态设计画廊 `design/2026-11-17/index.html`。Markdown 设计包适合在 GitHub 上阅读；上传这些文件不等于部署网站。

## 文档工作流

仓库参考 GacUI 的文档组织方式：根目录指示、知识库索引、专题规范、工作流提示词、任务日志与学习记录。工程文档使用英语；中文和日文 README 是明确面向读者的翻译版本。

面向编码助手的常用命令：

```text
ask Explain the publication and revocation rules.
design Plan the first P0 implementation slice.
plan Break the design into implementation steps and tests.
summarize Prepare the execution checklist.
execute and verify
kb Record the implemented design and verified commands.
```

也可以使用自然语言。编写文档的请求并不自动授权实现应用或部署。

## 验证文档

在仓库根目录的 PowerShell 中运行：

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

脚本检查本地 Markdown 链接、必需文件、需求标识、工作流标记、公开/本地边界，以及英语基线字符规则；翻译版 README 是明确允许的例外。不检查外部网址、标题锚点或应用行为。
