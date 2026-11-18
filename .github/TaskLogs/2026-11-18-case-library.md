# Database Case Library

## Goal and scope

Collect a sourced historical study and a fictional engineer portfolio as reusable cases. Preserve the shared database-driven public page rather than create person-specific components or serve fixture JSON directly. Personal imports use existing domain records and stay outside the repository.

## Changes

- Added the [case library](../../examples/case-library/README.md), two versioned source datasets and an acceptance checklist.
- Added a local/test-only importer that creates separate identities through existing owner operations, stores private import manifests outside Git and supports explicit local snapshot publication.
- Repeat import reuses recorded identities/publications; it refuses changed source data or identity/database mismatches rather than silently overwrite edits.
- Historical entries identify curator review and link to their sources. Fictional records label invented metrics and preserve an unpublished draft and private marker for scope tests.
- Added equivalent library navigation to the three README languages.
- No schema, public route or frontend rendering change was necessary. Relational authoring records and database publication snapshots already support `/p/{slug}`; the keyed router view remounts on parameter changes.

## Verification

- PHP syntax check and focused Pint check passed for the importer.
- Actual development-database import and repeat import passed. Both cases have three projects and one publication, with three and five public experiences respectively; the fictional sixth experience stays a private draft.
- Fresh unauthenticated Playwright checks passed for two cases, three interface languages and two viewport widths (12 combinations). No horizontal overflow or browser exceptions were observed. Desktop and mobile captures were visually reviewed.
- Project/skill dialogs, all eight selected experience-source endpoints and client-side slug changes passed. Unpublished and foreign-case source requests returned 404.
- Agent availability was checked separately. No live provider inference or semantic evaluation was performed.
- Initial documentation validation passed for 65 documents, and scoped diff whitespace/link checks passed. The final whole-repository check found a concurrent homepage document linking to the not-yet-created `2026-11-18-homepage.md` task log; that unrelated work was preserved. All links in this task's new documents resolve. Runtime addresses, identities, personal source provenance and raw results remain in ignored local documentation.

## Boundaries

The local HTML index is a convenience file, not a new public catalog route. The source datasets are not runtime data stores or personal production fixtures. No deployment, push, shared login or private-record publication is part of this change. Runtime-specific launches do not change the generic importer's default private behavior.

FINISHED: The requested reusable cases were collected and imported as database records.

VERIFIED: Import/repeat-import, database boundaries, browser rendering and route/source checks passed; model-answer quality was not evaluated.
