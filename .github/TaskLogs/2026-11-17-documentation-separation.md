# Shared and Local Documentation Separation

Date: 2026-11-17. Task: D2. Status: separation complete; verification scope below.

## Request and ownership

Keep general technology choices, standards, implementation details, development requirements, and product documentation in Git. Keep actual cloud/API services, credential locations, and demo-specific development problems locally.

## Changes

- Added an ignored `.local/` companion with an English entry point, environment/provider records, credential location references, and a demo work log.
- Preserved the original mixed deployment records locally and replaced shared environment details with portable deployment contracts.
- Updated shared entry points, guidelines, architecture/product descriptions, provenance, and backlog to use the same boundary.
- Removed workstation-specific absolute paths from shared documentation examples.
- Added validation that shared documentation cannot link to local-only files and that local/private files are not tracked accidentally.
- Preserved unrelated untracked design work and all functional/acceptance identifiers.

## Verification

- The scoped validation copy passed: 46 Markdown documents, 45 required files, 133 local links, all 39 feature IDs, 27 acceptance IDs, 12 integration-case IDs, and 9 stage markers.
- The copy excluded concurrently added UI design content and its navigation section only; the working checkout was not changed for this exclusion.
- Negative checks in an isolated Git fixture confirmed rejection of shared links into local-only documentation and force-tracked local files.
- All seven local companion/archive documents passed `git check-ignore`; sanitized environment-example paths remained eligible for tracking.
- `git diff --check` passed. A shared-document scan found no actual provider names, selected Region, project IDs, or workstation paths from the migrated environment records.
- Final full working-tree validation passed after the concurrent design files became available: 47 Markdown documents, 45 required files, 141 local links, all canonical identifiers, stage markers, and Git boundary checks. The UI design work was left unchanged by this task.

## Scope

Documentation and Git ignore rules only. No application behavior, cloud resources, API credentials, billing, or remote repository state was changed. Existing commit history was not rewritten; ignore rules do not remove previously committed information.
