# Release tooling implementation

Status: release tooling and first automated deployment verified. The user requested cloud deployment and automatic redeployment after Git pushes. Environment details and live resource state remain in `.local/docs/`.

- [x] Inspect the application runtime, queues, scheduler, persistence and publication constraints.
- [x] Implement isolated verification and production packaging with an explicit file allowlist.
- [x] Implement forced-command SSH delivery, unprivileged archive extraction, backups, atomic code switching and worker restart.
- [x] Add production configuration/database checks with regression tests.
- [x] Run PostgreSQL feature tests: 94 passed, 1,210 assertions.
- [x] Run PHP formatting, frontend production build, localization and documentation checks.
- [x] Verify archive rejection cases and final script/workflow validation: traversal, absolute paths, symbolic/hard links, devices, environment files and unintended roots are rejected.
- [x] Verify the release and rollback/recovery path in the target environment: a synthetic post-switch HTTP 503 restored the prior release; a fresh dump restored into an isolated database with matching core record counts; the temporary database was removed.
- [x] Verify an actual main-branch push passes the pipeline and updates the public revision: commit `636b866` completed verification and deployment successfully, and the public revision matched.
- [x] Verify live API authentication, secure cookies, cross-owner isolation, public field filtering/citation access, AI-disabled fallback and immediate publication/citation revocation through the delivery layer.
- [x] Verify browser login, compiled project rendering, desktop/mobile presentation and absence of console/runtime errors. The synthetic private project and identity survived the failure/recovery exercise.

Clean-checkout CI exposed two pre-existing environment assumptions: an empty, untracked Unit test directory and a public Agent test allowlist inherited from the local environment. Removed the nonexistent suite and made the synthetic Agent fixture's configuration explicit. The focused Agent suites passed with an empty external allowlist (33 tests, 343 assertions); production allowlists remain restrictive.

Verification limitations: deterministic AI tests do not establish production provider quality; the runtime starts with AI disabled. Database recovery was a logical dump restore, not a full-host disaster-recovery rehearsal. The single-host release uses a brief maintenance period.

Scope: no product feature or career data migration. CI contains no model API key and runs deterministic application tests. AI production enablement remains a separate explicit provider/data decision. The release script does not reverse database migrations.
