# CareerAtlas Business API Contracts

Status: suggested JSON contracts, not implemented endpoints. If bootstrap selects an alternative Vue transport, preserve these business semantics and document route mapping.

## Endpoint responsibilities

| Method and path | Purpose | Required checks |
| --- | --- | --- |
| GET/PATCH `/api/me/profile` | Read/update owner profile | Current authenticated owner; field validation |
| GET/POST `/api/projects` | List/create projects | Authorized space; no private count leaks |
| GET/PATCH `/api/projects/{id}` | Read/update project | Object authorization; expected version |
| POST `/api/projects/{id}/experiences` | Create draft experience | Parent-project permission |
| PATCH `/api/experiences/{id}` | Edit experience | Ownership/scope; expected-version conflict |
| POST `/api/experiences/{id}/confirm` | Confirm current facts | Required fields; exact version; duplicate handling |
| POST `/api/publications` | Publish selected snapshot | Allowed sources/fields; explicit owner action |
| POST `/api/publications/{id}/revoke` | Revoke publication | Owner authorization; synchronous access invalidation |
| GET `/api/public/{slug}` | Read public portfolio | Published snapshot only; active authorization |
| POST `/api/agent/sessions` | Start scoped conversation | Server-authorized mode and scope |
| POST `/api/agent/sessions/{id}/messages` | Ask a question | Session ownership, current scope, limits |

Authentication endpoints, skill/evidence maintenance, archive operations, and pagination contracts must be defined in the corresponding implementation slice. The table is not a claim of a complete implemented API.

## Mutation semantics

Use validated input; never mass-assign public/owner fields directly from model output. Use a transaction for confirming/publishing related records. Support an expected version and return a structured conflict rather than overwriting concurrent changes. Define idempotency for actions such as repeated confirmation/publication.

## Error contract direction

Return a stable application error code, a user-readable message, and field errors when applicable. Distinguish invalid input, unauthenticated access, unavailable records, version conflicts, rate limits, and provider unavailability. Choose a consistent object-disclosure policy for forbidden/missing records; do not expose private existence through errors.

## Session boundary

Bind authenticated owner sessions to the account. Bind anonymous visitor sessions to an unguessable session credential and one publication scope. The server must validate that binding on each request; knowing a session ID must not grant access.

## Evidence and public output

Validate link schemes and treat descriptions as untrusted content. Public payloads use explicit allowlists, not serialization of full models. Citation endpoints independently check current scope. Exports in P1 apply the same rules and disclose that downloaded copies cannot be recalled.
