---
pageClass: technical-reference
---

# Testing and verification

Checks cover business rules, publication boundaries, browser interactions and AI orchestration separately. Test data is fictional. A test command below is a reproducible procedure, not a claim that the current checkout has already passed it.

## Coverage and expected behavior

| Area | Example assertion | Source |
| --- | --- | --- |
| Identity and transport | Login renews the session; authenticated operations require identity and appropriate request protection | [IdentityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/IdentityTest.php), [TransportSecurityTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/TransportSecurityTest.php) |
| Ownership and consistency | Foreign objects cannot be read or linked; stale writes fail; related writes roll back together | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php) |
| Publication | Only selected confirmed fields appear; editing does not mutate a snapshot; revocation blocks dependent access | [PortfolioWorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/PortfolioWorkflowTest.php), [browser publication cases](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/e2e/publication.spec.ts) |
| P1 workflows | Capture application does not confirm or publish; answer history, practice retention and opportunity selection obey their own rules | [P1WorkflowTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/P1WorkflowTest.php) |
| AI boundaries | Foreign, cancelled, expired or stale runs cannot apply output; citations and proposal structure are checked | [AgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/AgentTest.php), [OwnerAgentTest](https://github.com/vivi-shiki/CareerAtlas/blob/main/tests/Feature/OwnerAgentTest.php) |
| UI and documentation | Navigation, forms, language switching, screenshots, guide links and built-page language/CSP | [Browser tests](https://github.com/vivi-shiki/CareerAtlas/tree/main/tests/e2e), [documentation validator](https://github.com/vivi-shiki/CareerAtlas/blob/main/scripts/check-docs.mjs) |

## Run backend checks safely

Complete [local setup](./development.md). Create a separate PostgreSQL database named `careeratlas_test` and configure its connection credentials in ignored `.env.testing`. Review [phpunit.xml](https://github.com/vivi-shiki/CareerAtlas/blob/main/phpunit.xml): it forces that database name. Also verify the host and credentials identify your test server. The feature tests use `RefreshDatabase`; never target valuable development or production data.

```sh
php artisan config:clear
php artisan test --compact
vendor/bin/pint --test
```

For a focused check after setting up the same test database:

```sh
php artisan test --filter=PortfolioWorkflowTest
php artisan test --filter=OwnerAgentTest
```

## Frontend and documentation

```sh
npm run build
npm run test:i18n
npm run test:docs
```

From PowerShell, also run:

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

The documentation checks verify chapter parity, feature coverage, links, localized assets and built HTML. They do not validate live model answers or external service availability.

## Browser and manual review

Use a separate local browser-test database and server. The [building guide](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/Guidelines/Building.md) describes the environment, migrations and test-only AI worker. The default test URL is loopback port 8001. Inspect the configured target before running; these tests create accounts and records.

```sh
npx playwright test --list
npm run test:e2e
```

A useful manual sequence is: create a fictional experience -> confirm -> select fields -> preview -> publish -> inspect from a logged-out browser -> edit privately -> explicitly republish -> revoke. Check that private notes never appear, a stale form reports a conflict and another account cannot open the record. Inspect all three interface languages and narrow layouts.

Deterministic AI tests use a fake provider in the testing environment. Real-model evaluation is a separate opt-in with selected non-sensitive data, provider configuration and output review. Evaluate citation support, evidence gaps and language, not only HTTP success.

## Reading validation evidence

The [P0 ledger](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p0-implementation.md) and [P1 ledger](https://github.com/vivi-shiki/CareerAtlas/blob/main/.github/TaskLogs/2026-11-18-p1-implementation.md) record dated acceptance, including the distinction between simulated and live-model checks. For the current revision, inspect the matching commit in [GitHub Actions](https://github.com/vivi-shiki/CareerAtlas/actions). Historical success does not prove a later revision, a different deployment or universal correctness.

See [backend implementation](./backend.md), [data lifecycle](./data-model.md) and [current limits](./privacy-and-ai.md).
