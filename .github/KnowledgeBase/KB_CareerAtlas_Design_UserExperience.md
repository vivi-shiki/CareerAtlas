# CareerAtlas User Experience

## P0 pages

| Page | Primary actions | Required states |
| --- | --- | --- |
| My profile | Edit, preview, publish, revoke | First use, unsaved changes, published with pending edits |
| Project list/detail | Create, filter, edit, archive | No projects, no results, unavailable record |
| Experience editor | Save draft, associate skills/evidence, confirm | Incomplete draft, field errors, conflict, success |
| Skill detail | Browse supporting projects and evidence | No associations, unavailable sources |
| Public portfolio | Read projects, ask questions, open citations | Loading, insufficient information, rate limit, revoked |

## Later pages

- AI capture: conversation beside an editable experience card; unknown facts visibly pending.
- Interview preparation: example selection, answer draft, one-question practice, optional saved feedback.
- Opportunity curation: selected fields, ordered projects, publication preview, source-change warning.
- Organization: memberships, authorized shared records, version-specific confirmations, discussion preparation.

## Record presentation

Keep personal contribution and team outcome visibly distinct. Display source/confirmation provenance without implying that a supplied URL was independently verified. Preserve occurrence-date precision. Show source freshness and publication time where they help interpretation.

## Conversation

Provide an input, suggested questions, answer area, source cards, operational progress, cancellation, and retry. Source cards show project, experience, and version time rather than internal IDs. Empty data yields an honest explanation. Manual browsing remains available during outages.

## Interaction quality

Associate labels with fields, locate errors next to inputs, and permit keyboard navigation of citations and primary actions. Do not communicate status through color alone. Warn before losing unsaved changes. Mobile users must be able to read projects, ask questions, and edit basic experiences.

## Languages

English is the repository working language. P1 preparation content supports English, Japanese, and Chinese; preserve names, technical terms, numbers, dates, and facts during translation. Select the initial product UI locale at implementation time; do not hard-code user-generated content to English.

## End-to-end MVP workflow

Record a project contribution -> confirm -> associate skills/evidence -> preview/publish -> ask about the contribution -> inspect the citation -> ask about an unrecorded skill -> verify the information-gap response -> revoke publication -> verify old access stops.
