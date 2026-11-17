# Building and Backend Verification

## Requirements

PHP 8.3+, Composer, PostgreSQL 16+, and Node.js 22+ with npm. Current locked packages include Laravel 13.43.0, Laravel MCP 0.5.9, Sanctum 4.5.0, PHPUnit 12.5.44, and the TypeScript MCP SDK 1.32.2. Lockfiles are authoritative for reproducible installation.

## Local setup

1. Run `composer install` and `npm ci`.
2. Copy `.env.example` to `.env` only if no environment file exists. Preserve existing credentials. Configure a dedicated development PostgreSQL database and run `php artisan key:generate` only when APP_KEY is missing.
3. Run `php artisan migrate` against the intended development database.
4. Create a separate PostgreSQL test database named `careeratlas_test` and configure ignored `.env.testing` with its connection. The PHPUnit configuration explicitly selects this database. Never point the tests at valuable data; RefreshDatabase resets the test schema.
5. Run `php artisan test --compact` and `vendor/bin/pint --test`.
6. Run `php artisan serve --host=127.0.0.1 --port=8000` for local API/MCP verification. This is a development server, not a deployment recipe.

Synthetic acceptance fixtures are separate from real career records.

## Frontend static review

Run `npm run build` for TypeScript checking and production asset compilation, then use the same local Laravel server. In local/testing environments only, `/design/editor`, `/design/publication`, `/design/portfolio`, `/design/projects`, `/design/profile`, `/design/skills`, and `/design/login` expose fictional visual fixtures. Append `?density=sparse` or `?density=dense` to review content extremes. These routes are presentation harnesses, not live business operations. They must never replace API failures with fictional data in the real application.

`npm run typecheck` checks TypeScript separately; `npm run dev` starts the Vite development server on loopback. `npx playwright install chromium` installs the test browser. Browser behavior commands are registered below. Captured QA images stay outside tracked source.

## Browser behavior tests

Create a separate PostgreSQL database named `careeratlas_e2e`, owned by the test role. Run migrations and the local Laravel server with `APP_ENV=testing`, `DB_DATABASE=careeratlas_e2e`, `SESSION_DRIVER=database` and `CACHE_STORE=array`; use an isolated local port. The array cache prevents unrelated synthetic cases from sharing throttle counters; explicit rate-limit UI behavior is tested with a controlled response. Keep the real deployed cache persistent. Do not run RefreshDatabase against the browser database.

`npm run test:e2e` targets `http://127.0.0.1:8001` by default; `E2E_BASE_URL` may select another loopback URL. Run `npx playwright test --list` to inspect the cases. Each test creates a new fictional owner through the real registration endpoint. The suite does not delete existing development data. `scripts/e2e-observe.php` refuses databases other than `careeratlas_e2e` and only reads synthetic owners. On Windows the observation helper invokes PHP through WSL; `E2E_WSL_DISTRO` selects a distribution when the system default is unsuitable. Elsewhere it uses PHP directly.

Results/traces are written to the operating system's temporary directory under `careeratlas-playwright-results`; these may contain synthetic session headers and are not publication artifacts. The JSON summary is `careeratlas-playwright-results.json`. Tests were written before live UI wiring. All 49 non-AI cases now pass; discovery alone is never acceptance.

## Developer MCP acceptance

With the local server running, run `php scripts/prepare-acceptance.php` to create a new fictional owner and an eight-hour, locally stored developer credential. It refuses nonlocal application URLs and environments. Then run `npm run test:mcp`. The runner uses the official MCP client to discover tools and execute a full workflow, including publication, immutable source edits, republication, and revocation. Credentials and detailed traces stay under ignored `.local/`; the public log records only sanitized outcomes.

A successful MCP check does not establish browser or Agent completion. Public Q&A uses a separate read-only tool scope.

## Agent runtime and verification

Configure the backend-only provider variables in an ignored environment file. `AI_ENABLED=false` preserves the manual-only experience. `AI_PUBLICATION_ALLOWLIST` accepts comma-separated approved publication slugs; an empty value disables questions everywhere, and `*` permits all otherwise-authorized public portfolios. Runtime service/model choices and credential locations belong in local operations documentation.

Run `php artisan queue:work database --queue=agent --sleep=1 --tries=1 --timeout=55` alongside the web application. Agent jobs explicitly use the database queue even if another default queue is configured. Use a persistent cache in a real runtime for session/IP limits. The scheduler must run `php artisan schedule:run` every minute so daily `agent:prune` cleanup executes; the prune command can also be run manually. Restart long-lived workers after code/config changes.

For deterministic browser Agent tests, use the isolated browser database/server and a separate worker with matching `APP_ENV=testing`, `AI_ENABLED=true`, `AI_DRIVER=fake`, and `AI_PUBLICATION_ALLOWLIST=*`. The fake driver is unavailable outside the testing environment and is never selected by request headers. These tests exercise real HTTP/session/queue/PostgreSQL behavior with a model double; they do not establish live model quality. Run `npx playwright test tests/e2e/agent.spec.ts` or the complete suite.

`php artisan test --filter=AgentTest` covers orchestration, authorization, expiry, cancellation, citation validation and transport. `php scripts/agent-evaluate.php --live` is an explicit opt-in live evaluation in a configured non-production environment. It creates only fictional fixtures and writes raw results under ignored `.local/`. `--smoke` runs the first case. The runner paces requests, validates structural/source expectations, and labels semantic review pending; a human/agent review of the actual answers is still required. See [Agent acceptance](../KnowledgeBase/KB_CareerAtlas_Agent_P0.md).

## Documentation

From the repository root in PowerShell, run:

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

It checks links, identifiers, stage markers, and the public/local documentation boundary. It does not test application behavior.

## Deployment preparation

Follow the [Deployment contract](../KnowledgeBase/KB_CareerAtlas_Design_Deployment.md). Concrete cloud services, provider configuration, credential locations, and machine commands belong in the ignored local runbook. No cloud deployment is implied by local acceptance.
