# CareerAtlas Case Library

Reusable demonstration content for evidence-linked portfolios and bounded Agent evaluation. English is the authored content language; the existing interface supports English, Simplified Chinese and Japanese. Interface language changes do not automatically translate career records.

## Cases

| Case | Basis | Content | Main purpose |
| --- | --- | --- | --- |
| [Grace Hopper](grace-hopper.json) | Curated historical public sources | Three projects and three confirmed study records: A-0, FLOW-MATIC and COBOL | Source attribution, historical dates, team contribution and knowledge boundaries |
| [Alex Morgan](alex-morgan.json) | Entirely fictional modern engineer | Three projects, five confirmed records and one unconfirmed draft | Capture, requirement mapping, answers, practice feedback, introductions and export translation |

Grace Hopper is included at the user's explicit request for a public historical test case. It is not her own resume or an endorsed account. The profile and records state that confirmation is curator review. The `learning` project category describes the historical study, not her employment. Skill labels are editorial classifications. We retain year precision, avoid unsupported measurements and omit awards to create an evidence-gap question. Facts are paraphrased, with links rather than copied articles or portraits.

Sources reviewed on 2026-11-18:

- [Yale University: Biography of Grace Murray Hopper](https://president.yale.edu/biography-grace-murray-hopper)
- [Computer History Museum: Grace Murray Hopper](https://computerhistory.org/profile/grace-murray-hopper/)

Alex Morgan is inspired by general themes of usability, reliability and standardization, not a renamed real person. All dates, projects, metrics and evidence statements are invented. A fictional evidence statement is not independent proof that a real test passed. Private-marker text in the fixture is synthetic test data, not a secret.

## Import and review

The [importer](../../scripts/import-case-library.php) runs only in a local/testing application with a loopback application URL. It creates separate case identities through the existing domain operations, preserves existing accounts and never resets the database.

```sh
php scripts/import-case-library.php
```

This imports private records. The declared confirmed records undergo curator confirmation; Alex's draft stays unconfirmed. To create the explicitly selected local demonstration snapshots:

```sh
php scripts/import-case-library.php --publish-local
```

Publication follows the existing preview digest and publication contract. Private notes and the unconfirmed draft are excluded. Credentials, record identifiers, publication slugs and a local HTML navigation page are written only under ignored `.local/case-library/`. Never put that directory in release artifacts. The importer does not enable AI, call a provider, deploy, commit or push.

Repeated imports reuse the local manifest without duplicating or replacing records. Changed datasets, database identity mismatches and identity collisions require manual review. A recorded publication is not automatically republished after revocation. Updating a case's source JSON does not change an existing publication.

## What visitors see

The current product has individual public portfolios at `/p/{slug}`, not a public directory of all accounts or cases. Visitors follow an explicitly shared link and see the selected profile, project summaries, personal contributions, linked skills and evidence. They can open project/skill/source details and use cited Q&A when that publication is enabled for Agent access. They cannot edit the case or enter the owner's private workspace through the public page.

Case source JSON and this README are a developer-facing library. They are not web-served from the application's public root. The generated local HTML index links the two demonstration pages; it is a convenience file, not a new application route. The public UI does not read these JSON files directly.

A loopback URL works on the host computer only. External access requires an actual application deployment and publication in that environment. Sharing or pushing the repository does not publish its database records. Public links are readable without login by anyone who can reach the server; they are not recipient-specific access grants.

A future website-level case gallery could list a deliberate allowlist of published case links with historical/fictional labels and a brief explanation. Such a gallery is not implemented here and must not enumerate private owners or drafts. Opportunity pages remain independent publications, not a shared catalog.

## Verification

Use the [case acceptance checklist](test-plan.md). Runtime addresses, exact commands and observed results belong in ignored local documentation. Keep structural/browser checks separate from live-model semantic evaluation.
