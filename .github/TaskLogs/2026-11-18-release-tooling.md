# Release tooling implementation

Status: in progress. The user requested cloud deployment and automatic redeployment after Git pushes. Environment details and live resource state remain in `.local/docs/`.

- [x] Inspect the application runtime, queues, scheduler, persistence and publication constraints.
- [x] Implement isolated verification and production packaging with an explicit file allowlist.
- [x] Implement forced-command SSH delivery, unprivileged archive extraction, backups, atomic code switching and worker restart.
- [x] Add production configuration/database checks with regression tests.
- [x] Run PostgreSQL feature tests: 94 passed, 1,210 assertions.
- [x] Run PHP formatting, frontend production build, localization and documentation checks.
- [ ] Verify archive rejection cases and final script/workflow validation.
- [ ] Verify the release and rollback/recovery path in the target environment.
- [ ] Verify an actual main-branch push passes the pipeline and updates the public revision.

Scope: no product feature or career data migration. CI contains no model API key and runs deterministic application tests. AI production enablement remains a separate explicit provider/data decision. The release script does not reverse database migrations.
