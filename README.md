# CareerAtlas

[English](README.md) · [Chinese](README.zh-CN.md) · [Japanese](README.ja.md)

**Your AI Agent for evidence-backed career stories.**

CareerAtlas connects projects, contributions, skills, and evidence with an **AI Agent that helps you capture experience, prepare for interviews, and answer portfolio questions with sources**. English, Simplified Chinese, and Japanese are built in.

**Stack:** PHP 8.3+ (**Laravel 13 backend framework**) · Vue 3 · TypeScript · PostgreSQL.

[AI Agent walkthrough](docs/en/ai-agent.md) · [User guide](docs/en/index.md) · [Quick start](docs/en/quickstart.md) · [Development](docs/en/development.md)

## Meet your AI Agent

- **Capture and prepare:** turn notes into editable experience drafts, map requirements to selected evidence, draft answers, and review written practice.
- **Present and explain:** draft opportunity introductions, review export translations, and let visitors ask questions with clickable source citations.
- **Stay in control:** the Agent works within selected sources; you review, confirm, and publish. Availability depends on operator configuration, with manual workflows available throughout.

![AI Agent answering a portfolio question with a source citation](docs/assets/screenshots/en/qa.jpg)

*Actual application UI with fictional data and a deterministic test-provider answer; this demonstrates the workflow, not live model quality. [Follow the AI Agent tutorial](docs/en/ai-agent.md).*

## What you can do

- **Record:** organize projects, distinguish personal actions from team outcomes, link skills and evidence, and keep revision history.
- **Capture:** save notes, review optional AI drafts, compare duplicates, then confirm the resulting experience yourself.
- **Prepare:** select sources, draft and version answers, practice in writing, and retain useful feedback.
- **Share:** preview selected fields, publish a portfolio with cited Q&A, and revoke access.
- **Tailor:** create independent opportunity pages and export reviewed Markdown, including optional translations.

P0 and P1 are implemented. Organizations, named-recipient grants, and reviewer confirmation remain planned. AI never confirms or publishes for you; manual workflows work without a provider. [Privacy and current limits](docs/en/privacy-and-ai.md).

## Use it

1. Open the homepage, choose **Sign in**, and create an account if needed.
2. Add a project and an experience; attach evidence and confirm the facts.
3. Follow the [AI Agent walkthrough](docs/en/ai-agent.md) to draft an answer or publish a portfolio and explore cited Q&A. Review and confirm drafts before sharing.

See the [illustrated guide](docs/en/index.md) for every workflow, recovery step, and three-language screenshot.

## Architecture

| Layer | Stack and purpose |
| --- | --- |
| Interface | Vue 3, TypeScript, Vite, Vue I18n |
| Application | PHP 8.3+ (Laravel 13 backend framework), shared HTTP/MCP operations and server-side authorization |
| Data and AI | PostgreSQL, versioned publication snapshots, database queue, bounded model adapter |
| Documentation | VitePress, static pages and local search, shipped with the application |

[Architecture and code map](docs/en/architecture.md) · [API and MCP](docs/en/mcp.md). Exact dependencies are pinned in lockfiles.

## Run locally

Requires PHP 8.3+, Composer 2, Node.js 22+, and PostgreSQL 16+. Clone this repository and run the following from its root in a POSIX shell. Create the database and role first; only copy the environment file and generate the application key on first setup.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
# Configure a PostgreSQL database and its credentials in .env first.
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000`. AI is disabled by default. [Full setup](docs/en/development.md) covers PHP extensions, Windows/WSL, workers, the scheduler, and isolated test databases.

## Develop and contribute

`npm run dev` starts product hot reload; `npm run docs:dev` previews documentation. `npm run build` builds both. Verify with `npm run test:i18n`, `npm run test:docs`, and the relevant PHP/browser tests against a separate local test database.

Read [AGENTS.md](AGENTS.md) and the [engineering knowledge base](.github/KnowledgeBase/Index.md). Use focused pull requests with verification evidence. Keep secrets, real career data, and `.local/` out of Git. See the [fictional case library](examples/case-library/README.md) for example portfolios and optional import instructions.
