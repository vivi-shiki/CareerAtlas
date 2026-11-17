# CareerAtlas Business API Contracts

Status: P0 non-AI backend implemented; frontend and Agent acceptance remain separate gates. Authentication uses same-origin session cookies with CSRF protection. The concrete transport is a JSON operation API over shared domain services.

## Routes

| Method and path | Purpose | Boundary |
| --- | --- | --- |
| GET `/api/csrf` | Initialize session and obtain CSRF token | Send the token in X-CSRF-TOKEN on browser mutations |
| POST `/api/auth/register` | Create an owner identity | Name, unique email, confirmed password of at least 12 characters |
| POST `/api/auth/login` | Authenticate and regenerate session | Rate-limited; generic invalid-credential error |
| GET `/api/auth/me` | Read current identity | Authenticated owner |
| POST `/api/auth/logout` | Invalidate session | Authenticated owner, CSRF |
| POST `/api/operations/{operation}` | Execute a named business operation | Authenticated owner, CSRF, schema and object authorization |
| GET `/api/public/{slug}` | Read the active publication | Selected snapshot fields and current source authorization |
| GET `/api/public/{slug}/projects/{id}` | Read selected project experiences | No access to unselected projects |
| GET `/api/public/{slug}/skills` | Read public skill counts | Distinct permitted experiences/projects only |
| GET `/api/public/{slug}/skills/{id}` | Read supporting public experiences | Same projection and current access checks |
| GET `/api/public/{slug}/experiences/{id}` | Resolve a source/citation | Current publication and source access required |
| POST `/mcp/career` | Streamable HTTP developer MCP | User-bound bearer credential with career:manage ability; cookie sessions alone are insufficient |

`contracts/operations.json` is the exact name/input schema inventory: 36 owner operations plus five public reads. [Implementation design](KB_CareerAtlas_Implementation_P0.md) explains the operation families and storage. This concrete mapping supersedes the earlier suggested resource routes; it preserves their business semantics. Successful business responses use `{ "data": ... }`; list pagination returns items, total, page, and per_page.

## Mutations and errors

Ownership comes from the authenticated principal. Mutations run within transactions and carry expected versions where they modify existing content. Confirmation is idempotent for an exact version. Publication requires an explicitly reviewed preview digest and an idempotency key; replay with different input fails. Archive, deletion, outward source revocation, and publication revocation are separate operations.

Errors contain code and message, plus field-keyed errors for validation. Invalid input uses 422, missing/foreign objects share the same 404 body, conflicts use 409, unauthenticated access uses 401, and limits use 429. Browser writes without valid CSRF use 419. Responses are not cached across visibility changes. External links are validated but never fetched or executed.

Domain, validation, and unavailable messages support `Accept-Language` for English, Simplified Chinese, and Japanese. Additive `message_key` and `error_keys` preserve the original system messages so clients can change display language without repeating a mutation. Status codes, domain codes, field keys, and payload values are unchanged. See [Localization](KB_CareerAtlas_Localization.md).

## MCP and Agent separation

The developer MCP surface uses the official Laravel MCP implementation and the same domain dispatcher as the browser API. Tool schemas reject unknown fields and never accept owner IDs as authority. Tools return structured data or explicit tool errors. Read operations cannot bypass current publication visibility.

The P0 portfolio Agent receives only scoped read tools and a terminal answer schema. Developer mutation tools are not exposed to it. The [Agent route table](KB_CareerAtlas_Agent_P0.md) defines availability, conversation, question, polling and cancellation endpoints. They bind a random browser principal and publication scope on the server; knowing a conversation/run identifier does not authorize access. Jobs run on the database `agent` queue, independently of the HTTP request.
