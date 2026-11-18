# Public portfolio AI Agent copy

## Scope and changes

The user requested prominent AI Agent wording in the public portfolio question panel, synchronized across English, Simplified Chinese, and Japanese, and publication through the existing release pipeline.

Both the heading and introductory sentence now name AI Agent. The introduction explains that questions about projects, skills, and contributions are answered using only the current published portfolio. Existing translation keys and question-panel behavior are preserved.

## Verification

- Production build, translation parity checks, and reader-documentation integrity checks passed.
- Read-only Playwright checks verified both translated strings in all three languages at desktop and mobile widths (1440, 390, and 320 pixels), with no horizontal overflow, clipped headings, browser errors, or model requests. Japanese desktop and mobile captures were visually inspected.
- Unrelated case-library and importer changes were excluded. Release identifiers and live checks belong in the ignored operations log.

## Status

FINISHED: synchronized AI Agent question-panel copy.

VERIFIED: local build, translation/document integrity, and rendered checks described above. Live release verification is recorded separately after the authorized push.
