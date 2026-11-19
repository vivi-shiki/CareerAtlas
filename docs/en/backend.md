---
pageClass: technical-reference
---

# Laravel backend implementation

CareerAtlas uses PHP 8.3+ (**Laravel 13 backend framework**) for a single application. Laravel handles authentication, routing, persistence and queued work; application services implement the career-record rules. Vue renders the interface and does not replace server-side authorization.

## Scope and code map

| Responsibility | Implementation |
| --- | --- |
| Registration, login and session renewal | [IdentityController](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Http/Controllers/IdentityController.php), Laravel Auth and request validation |
| Authenticated JSON operations | [Web routes](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/web.php), OperationController and session/CSRF middleware |
| Operation contracts and transactions | [OperationDispatcher](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/OperationDispatcher.php), [Input](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Input.php) and [contracts](https://github.com/vivi-shiki/CareerAtlas/tree/main/contracts) |
| Ownership and stale-write protection | [Store](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/Store.php): owner-scoped queries and expected-version checks |
| Confirmation and public snapshots | [ExperienceService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/ExperienceService.php), [PublicationService](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Domain/PublicationService.php) and [PublicationProjection](https://github.com/vivi-shiki/CareerAtlas/blob/main/app/Services/PublicationProjection.php) |
| AI execution | [Jobs](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Jobs), [Agent services](https://github.com/vivi-shiki/CareerAtlas/tree/main/app/Agent), database queue and scheduled cleanup |
| Developer integration | [MCP routes](https://github.com/vivi-shiki/CareerAtlas/blob/main/routes/ai.php), Sanctum tokens, scope checks and the same operation dispatcher |

The implementation uses Eloquent for user identity and Laravel's query builder for much of the domain storage. Ownership checks live in domain services; this is not a claim that every entity has an Eloquent model or a separate Policy class. Identity inputs use Laravel validation; business-operation inputs use the shared schema validator and domain checks.

## Trace one update

1. The browser sends an authenticated JSON request to `POST /api/operations/update_experience`, including the record ID and `expected_version`.
2. The controller derives the user from the session. A caller cannot choose the owner by adding a field.
3. The dispatcher validates the operation schema and opens a transaction. It locks that owner's user row to serialize operations for that owner.
4. The experience service checks ownership and the current version, applies the update and marks the record as a draft.
5. Revision and audit records are written in the operation transaction. A failure rolls back the related writes.
6. The controller returns the saved record. The Vue form renders errors or the updated version. The previously published snapshot is not silently replaced.

Foreign or unavailable objects return 404; invalid inputs return 422; stale-version conflicts return 409. See [API and MCP](./mcp.md) and [tests](./testing.md).

## Why these choices

- **One application:** the browser and MCP reuse business rules, reducing divergence between two editing interfaces.
- **Explicit snapshots:** saving private work and publishing selected facts are separate actions. See [the data model](./data-model.md).
- **Version checks and transactions:** an old form cannot silently overwrite a newer edit, and partial publication writes can be rolled back.
- **Background AI work:** model latency is handled through queued runs with cancellation, deadlines and status polling. Manual operations remain usable without AI.
- **Server-side source scope:** public Q&A reads the selected publication; owner drafting has its own selected-source context. Model output cannot directly confirm or publish content.

## Boundaries

P0 and P1 are implemented; organization collaboration and named-recipient grants remain planned. Attached URLs are not automatically fetched as proof. Owner confirmation is not third-party verification. Queue processing requires a worker; expiry cleanup requires a scheduler. A successful deterministic AI test does not establish live-model quality.

Continue with [architecture](./architecture.md), [data and publication](./data-model.md), [local setup](./development.md) and [testing](./testing.md).
