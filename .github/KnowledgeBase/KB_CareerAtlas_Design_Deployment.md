# CareerAtlas Deployment Contract

Status: shared design requirements. Application deployment tooling is not implemented. Actual environment selections and observations belong in ignored local documentation.

## Application requirements

| Area | Shared contract |
| --- | --- |
| Runtime | Run the supported PHP/Laravel application and deliver the compiled Vue/TypeScript UI; prefer the same origin initially |
| Persistence | Use PostgreSQL; preserve records and publication versions across releases |
| Network | Serve public traffic over HTTPS; restrict database access to authorized application/maintenance paths |
| Model integration | Call providers through the server-side adapter; preserve bounded tool execution and the manual fallback |
| Configuration | Inject environment values at runtime; keep credentials out of source, browser bundles, artifacts, and logs |
| Operations | Provide health checks, redacted diagnostics, backup/restore procedures, and reproducible releases |

These contracts do not prescribe a cloud subscription, hosting service, API subscription, model identifier, or deployment location. Preserve shared application behavior when mapping them to an installation.

## Configuration boundary

Public documentation specifies configuration purpose, validation, defaults where safe, and required versus optional values. Sanitized examples use placeholders. Exact variable names become authoritative when implementation defines them; no runtime configuration schema exists yet.

Keep actual provider/service choices, model settings, project/resource identifiers, domains, regions, CLI profiles, quotas, costs, credential locations, and environment-specific commands in `.local/docs/`. Keep secret values in the credential mechanism, never in documentation.

Provider adapter code, generic integration contracts, dependency versions, migrations, and parameterized infrastructure or release scripts remain versioned product implementation. Do not hide reusable implementation detail in local notes merely because it interacts with an external service. Actual selected-provider configuration and research remain local.

## Release procedure

1. Build and test a reproducible artifact from a known revision and pinned dependencies.
2. Validate required configuration and dependency compatibility without exposing secret values.
3. Confirm the target environment's access, capacity, and restrictions using its local runbook.
4. Prepare backup and recovery steps before schema changes; application rollback does not imply safe reversal of every migration.
5. Release the artifact and apply authorized migrations while preserving persistent data.
6. Check health, authentication, owner isolation, public field filtering, citations, revocation, and AI failure behavior.
7. Record reusable verification results in shared task logs and concrete environment evidence in local logs.

## Persistence and access invariants

A release must not reset career records or publication state. A cache or delivery layer must preserve revocation and access-scope semantics. Backups need an explicit retention policy and a verified restore procedure when implemented. Logs must not expose career content, credentials, or raw private model prompts.

## Open implementation decisions

Select compatible runtime versions, frontend integration, artifact packaging, migration/recovery tooling, and measurable backup/recovery objectives during implementation. Record portable commands only after verification. The local companion records service-specific choices and does not establish a new product requirement by itself.

Related: [Architecture](KB_CareerAtlas_Design_Architecture.md), [Building](../Guidelines/Building.md), [Testing](../Guidelines/Testing.md), [Documentation ownership](../Guidelines/Documentation.md).
