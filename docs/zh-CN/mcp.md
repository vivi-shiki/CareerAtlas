# API 与 MCP

## 共用业务操作

已认证的 HTTP 操作入口为 `POST /api/operations/{operation}`，浏览器请求使用会话与 CSRF 防护。开发者 MCP 入口为 `/mcp/career`，需要有效的 bearer token，且具有 `career:manage` 权限；它不是匿名公开聊天接口。

两种接口使用相同的输入模式与所有权检查。目前目录包含 80 个操作，覆盖资料、项目、经历、技能、证据、发布、素材、历史、准备、练习、机会、导出和本人 AI 生命周期。

| 需求 | 代表操作 |
| --- | --- |
| 读取和修改经历 | `get_experience`、`update_experience`、`confirm_experience` |
| 检查和管理发布 | `preview_publication`、`publish_publication`、`revoke_publication` |
| 面试准备 | `create_preparation`、`create_prepared_answer` |
| 管理 AI 草稿 | `start_owner_ai`、`get_owner_ai`、`cancel_owner_ai`、`apply_owner_ai` |

通过 MCP `tools/list` 或 [API 清单](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md)查看准确名称和输入模式。写操作通常需要 `expected_version`；发布需要已检查预览的摘要和幂等键。令牌不会绕过这些检查。

## 本地验收

在隔离的本地开发数据库中执行 `php scripts/prepare-acceptance.php`，会创建虚构验收记录与短期令牌，将客户端配置写入被忽略的 `.local/`。然后运行 `npm run test:mcp`。不要发布生成的配置，也不要在线上运行此测试数据准备步骤。

在 MCP 客户端的安全凭证设置中配置本地 `/mcp/career` 地址及令牌。不要把 bearer token 放进 URL、截图、README 示例或提交。验收令牌在八小时后过期，需要时重新生成本地测试数据。

公开作品集问答有独立的页面范围工具边界，无法发现或调用本人的完整工具目录。异步辅助功能需要队列进程，见[本地开发](./development.md)。
