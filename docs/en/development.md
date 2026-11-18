# Local development

## Prerequisites

PHP 8.3+ with PDO PostgreSQL, mbstring, intl and bcmath; Composer 2; Node.js 22+ and npm; PostgreSQL 16+. The commands below use a POSIX shell. On Windows, use WSL for PHP/PostgreSQL or adapt commands to your installed runtime. PowerShell file copying uses `Copy-Item` instead of `cp`.

## First run

```sh
git clone https://github.com/vivi-shiki/CareerAtlas.git
cd CareerAtlas
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Copy the environment example only on first setup; do not overwrite an existing configuration or regenerate an established application's key. Create a PostgreSQL database and role you control, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Set `APP_URL` to your local address.

```sh
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000`, then register through the UI. AI is disabled by default. The compiled docs are at `/docs/en/index.html`, `/docs/zh-CN/index.html`, and `/docs/ja/index.html`.

## Daily workflow

Run `npm run dev` alongside Laravel for product hot reload. Use `npm run docs:dev` for documentation editing; it provides its own preview server. The product-home link requires the Laravel host when previewing documentation separately.

If enabling AI, configure `AI_ENABLED`, `AI_DRIVER`, `AI_MODEL`, `GEMINI_API_KEY`, and the owner/public allowlists in `.env`; see `config/agent.php` for exact controls. Keep credentials server-side. Run these in separate terminals:

```sh
php artisan queue:work database --queue=agent --sleep=1 --tries=1 --timeout=55
php artisan schedule:work
```

The fake provider is for isolated tests, not an online feature. Do not run fixture importers or destructive test resets against an existing user database.

## Verify a change

```sh
npm run build
npm run test:i18n
npm run test:docs
php artisan test --compact
vendor/bin/pint --test
```

Run the PHP suite with a separate PostgreSQL test database configured according to [building guidance](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md). Browser tests also create synthetic users and records: point `E2E_BASE_URL` at a local test server, then run `npm run test:e2e`. Never target production. Use PowerShell to run `.github/Scripts/Validate-Documentation.ps1` for engineering-document links and the public/private boundary.

## Maintain documentation

Keep the same chapter filenames in all three locales. Update screenshots when a flow changes. To regenerate the fictional screenshots, start an isolated local test server, then run:

```sh
CAPTURE_DOCS=1 E2E_BASE_URL=http://127.0.0.1:8001 npx playwright test tests/e2e/docs-capture.spec.ts
```

Normal browser runs skip capture. `npm run build` builds both app and docs; the release package includes the generated static output. Build finalization externalizes VitePress inline scripts to work with the application's strict script policy.

To also regenerate AI review/Q&A images, set `CAPTURE_DOCS_AI=1` with a matching test-only fake-provider server and `agent` worker. These images demonstrate workflow behavior, not live model quality.

Before contributing, read [AGENTS.md](https://github.com/vivi-shiki/CareerAtlas/blob/main/AGENTS.md), use a focused branch, and include relevant verification in the pull request. Keep `.env`, tokens, and `.local/` out of Git. See the [fictional case library](https://github.com/vivi-shiki/CareerAtlas/tree/main/examples/case-library) for optional import examples; they are not installed automatically.
