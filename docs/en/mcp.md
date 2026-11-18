# API and MCP

## Shared operations

The authenticated HTTP operation surface is `POST /api/operations/{operation}`. Browser requests use a session and CSRF protection. The developer MCP endpoint is `/mcp/career`; it requires a valid bearer token with the `career:manage` ability. It is not an anonymous public chat endpoint.

Both interfaces use the same schemas and ownership checks. The current catalog contains 80 operations covering profile, projects, experience, skills, evidence, publication, capture, history, preparation, practice, opportunities, exports, and owner AI lifecycle.

| Need | Representative operations |
| --- | --- |
| Read and edit a record | `get_experience`, `update_experience`, `confirm_experience` |
| Review a publication | `preview_publication`, `publish_publication`, `revoke_publication` |
| Prepare | `create_preparation`, `create_prepared_answer` |
| Manage assisted drafts | `start_owner_ai`, `get_owner_ai`, `cancel_owner_ai`, `apply_owner_ai` |

Discover exact names and input schemas through MCP `tools/list` or the [API inventory](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md). Writes commonly require `expected_version`; publication requires the reviewed preview digest and an idempotency key. A token does not bypass these safeguards.

## Local acceptance

For an isolated local development database, `php scripts/prepare-acceptance.php` creates fictional acceptance records and a short-lived token, writing the client configuration under ignored `.local/`. Run `npm run test:mcp` against that setup. Do not publish the generated configuration or run this fixture setup on production.

Configure your MCP client with the local `/mcp/career` URL and token using its secure credential settings. Do not put bearer tokens in URLs, screenshots, README examples, or commits. The acceptance token expires after eight hours; create a fresh local fixture when needed.

Public portfolio Q&A has a separate page-scoped tool boundary. It cannot discover or invoke this owner's full tool catalog. For asynchronous assistance, a queue worker must be running; see [local development](./development.md).
