# Architecture

CareerAtlas is one Laravel application with a Vue workspace, a public portfolio, and a statically built documentation site.

```text
Vue 3 / TypeScript UI      Authenticated MCP client
          │                         │
          └──── Laravel operations ──┘
                 │          │
          PostgreSQL    Database queue → model adapter
                 │
       Selected versioned publication → public page / Q&A

VitePress → public/docs → same-origin static documentation
```

## Components

| Layer | Responsibility |
| --- | --- |
| Laravel 13 / PHP 8.3+ | Sessions, authorization, validation, transactions, version checks, publication projections |
| Vue 3 / TypeScript / Vite | Workspace forms, source previews, dialogs, visitor Q&A |
| Vue I18n | English, Simplified Chinese, Japanese UI catalogs |
| PostgreSQL | Owned career records, immutable versions, publication snapshots, queue state |
| Model provider adapter | Bounded asynchronous owner assistance and page-scoped public Q&A |
| VitePress | Pre-rendered guides, local search, language navigation; no documentation database |

Lockfiles are authoritative for dependency versions. HTTP operations and the developer MCP server share application services and schemas. Public Q&A uses a separate, restricted surface; it cannot call arbitrary owner mutations.

## Data flow

An owner saves a draft, confirms a source version, selects public fields, reviews a preview, and publishes a snapshot. Optimistic version checks reject stale writes. Current access is checked again for publication, source retrieval, and AI application. Editing a source does not silently rewrite a published snapshot.

The worker processes the `agent` queue; a scheduler handles expired practice and proposals. Manual workflows are independent of model availability. Documentation is built into `public/docs` and travels with the application release.

## Code map

| Path | Contents |
| --- | --- |
| `app/Domain`, `app/Agent`, `app/Mcp` | Domain behavior, model integration, developer tools |
| `contracts`, `routes`, `database` | Operation schemas, entry points, migrations |
| `resources/js`, `resources/css` | Product UI, translations, styles |
| `docs` | Trilingual reader documentation and fictional screenshots |
| `tests`, `scripts`, `deploy` | Verification, development helpers, release tooling |

Deeper contracts: [architecture](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Architecture.md), [authorization](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_Design_Authorization.md), [API inventory](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/KnowledgeBase/KB_CareerAtlas_API.md).
