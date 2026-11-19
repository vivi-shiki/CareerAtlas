---
pageClass: technical-reference
---

# Laravel 后端实现

CareerAtlas 使用 PHP 8.3+（**Laravel 13 后端框架**）构建单一应用。Laravel 负责认证、路由、持久化与队列任务，业务服务实现职业记录规则。Vue 负责界面展示，服务端权限检查仍由后端执行。

## 实现范围与源码导航

| 职责 | 实现位置 |
| --- | --- |
| 注册、登录、会话更新 | [IdentityController](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Http/Controllers/IdentityController.php)、Laravel Auth 与请求校验 |
| 已认证的 JSON 业务操作 | [Web 路由](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/web.php)、OperationController、会话和 CSRF 中间件 |
| 操作契约与事务 | [OperationDispatcher](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/OperationDispatcher.php)、[Input](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Input.php) 与 [contracts](https://github.com/vivi-shiki/CareerAtlas/tree/main/contracts) |
| 所有权与过期更新保护 | [Store](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Store.php)：按所有者查询和预期版本检查 |
| 确认与公开快照 | [ExperienceService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/ExperienceService.php)、[PublicationService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/PublicationService.php)、[PublicationProjection](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Services/PublicationProjection.php) |
| AI 执行 | [队列任务](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Jobs)、[Agent 服务](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Agent)、数据库队列与定时清理 |
| 开发者集成 | [MCP 路由](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/ai.php)、Sanctum 令牌、范围检查及共用操作分发器 |

用户身份使用 Eloquent，大部分领域数据通过 Laravel 查询构建器读写。所有权检查位于领域服务中，并不意味着每个实体都有 Eloquent 模型或独立 Policy 类。身份请求使用 Laravel 校验，业务操作使用共享模式校验器和领域规则。

## 跟踪一次更新

1. 浏览器向 `POST /api/operations/update_experience` 发送已认证的 JSON 请求，携带记录 ID 与 `expected_version`。
2. 控制器从会话取得用户身份，调用方不能通过附加字段指定其他所有者。
3. 分发器校验操作模式并开启事务，锁定该用户行以串行处理同一所有者的操作。
4. 经历服务检查所有权和当前版本，写入修改并将记录标记为草稿。
5. 修订历史和审计记录在该操作事务中写入；失败时回滚相关写入。
6. 控制器返回保存后的记录，Vue 表单展示错误或新版本；已有公开快照不会被悄悄替换。

不属于当前用户或不可用的对象返回 404，输入错误返回 422，过期版本冲突返回 409。参见 [API 与 MCP](./mcp.md)和[测试说明](./testing.md)。

## 设计理由

- **单一应用**：浏览器和 MCP 复用业务规则，减少两套编辑入口的行为差异。
- **显式快照**：保存私人记录与发布选定事实是两个操作，详见[数据模型](./data-model.md)。
- **版本检查与事务**：旧表单不能无提示覆盖新修改，发布失败可回滚相关写入。
- **后台 AI 任务**：通过队列、取消、期限和状态轮询处理模型延迟；没有 AI 仍可进行手动操作。
- **服务端来源范围**：公开问答读取选定发布内容，本人辅助使用独立的选定来源上下文；模型输出不能直接确认或发布。

## 当前边界

P0、P1 已实现，组织协作与指定接收人授权仍在规划中。附加网址不会被自动抓取并视为证明，用户确认也不等于第三方验证。队列需要运行 worker，过期清理需要 scheduler。固定输出的 AI 测试通过，不代表真实模型质量已通过验证。

继续阅读[技术架构](./architecture.md)、[数据与发布](./data-model.md)、[本地开发](./development.md)和[测试说明](./testing.md)。
