---
pageClass: technical-reference
---

# 测试与验证

业务规则、发布边界、浏览器交互和 AI 编排分别验证，测试数据使用虚构内容。下列命令是可复现步骤，不表示当前检出的版本已经执行并通过这些测试。

## 覆盖范围与预期行为

| 范围 | 断言示例 | 源码 |
| --- | --- | --- |
| 身份与传输 | 登录更新会话，受保护操作需要身份及适当的请求保护 | [IdentityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/IdentityTest.php)、[TransportSecurityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/TransportSecurityTest.php) |
| 所有权与一致性 | 不能读取或关联他人对象，过期更新失败，相关写入一起回滚 | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php) |
| 发布 | 只显示选定的确认字段，编辑不改变快照，撤销阻断依赖访问 | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php)、[浏览器发布用例](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/e2e/publication.spec.ts) |
| P1 流程 | 采用素材草稿不会直接确认或发布；回答历史、练习保留和机会选择遵守各自规则 | [P1WorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/P1WorkflowTest.php) |
| AI 边界 | 他人的、已取消、已过期或来源失效的运行不能应用结果；检查引用与建议结构 | [AgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/AgentTest.php)、[OwnerAgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/OwnerAgentTest.php) |
| 界面与文档 | 导航、表单、语言切换、截图、文档链接及构建页面的语言和 CSP | [浏览器测试](https://github.com/vivi-shiki/CareerAtlas/tree/main/tests/e2e)、[文档检查器](https://github.com/vivi-shiki/CareerAtlas/blob/main/scripts/check-docs.mjs) |

## 安全运行后端测试

完成[本地开发设置](./development.md)，创建独立的 PostgreSQL 数据库 `careeratlas_test`，在不提交 Git 的 `.env.testing` 中配置连接凭据。[phpunit.xml](https://github.com/vivi-shiki/CareerAtlas/blob/main/phpunit.xml)强制使用此数据库名，还需核对主机和凭据确实指向测试服务器。功能测试使用 `RefreshDatabase`，不能指向有价值的开发或生产数据。

```sh
php artisan config:clear
php artisan test --compact
vendor/bin/pint --test
```

设置好同一个测试数据库后，可以只检查相关部分：

```sh
php artisan test --filter=PortfolioWorkflowTest
php artisan test --filter=OwnerAgentTest
```

## 前端与文档检查

```sh
npm run build
npm run test:i18n
npm run test:docs
```

另外在 PowerShell 中运行：

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

文档检查覆盖三语章节一致性、功能映射、链接、本地化素材及构建 HTML，不验证真实模型回答或外部服务可用性。

## 浏览器与人工检查

使用独立的本地浏览器测试数据库和服务。[构建指南](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md)说明环境、迁移和测试专用 AI worker。默认地址为本机 8001 端口；运行前核对实际目标，这些测试会创建账号与记录。

```sh
npx playwright test --list
npm run test:e2e
```

建议人工流程：创建虚构经历 → 确认 → 选字段 → 预览 → 发布 → 在未登录浏览器查看 → 私下修改 → 显式重新发布 → 撤销。检查私人备注未出现、过期表单提示冲突、其他账号不能打开该记录，并检查三语界面和窄屏布局。

确定性 AI 测试在 testing 环境使用固定输出的测试提供方。真实模型评估需独立启用，选择非敏感数据、配置提供方并审核输出。应检查引用是否支持结论、证据缺口和语言，而不只检查 HTTP 成功。

## 如何阅读验证证据

[P0 验证记录](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p0-implementation.md)与 [P1 验证记录](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p1-implementation.md)保留带日期的验收结果，并区分模拟与真实模型检查。当前版本应查看 [GitHub Actions](https://github.com/vivi-shiki/CareerAtlas/actions)中对应提交的运行。历史通过不能证明后续版本、另一部署环境或所有情况都正确。

另见[后端实现](./backend.md)、[数据生命周期](./data-model.md)与[当前限制](./privacy-and-ai.md)。
