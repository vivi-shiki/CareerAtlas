# CareerAtlas

**An AI-powered career portfolio and skills knowledge system that connects personal experience, project demonstrations, contributions, and evidence, and makes authorized records searchable through conversation.**

CareerAtlas is a standalone product for individuals and organizations. It helps people document their work, present their capabilities, and prepare for opportunities. Future organization features help employees share experience and support development conversations. These documents define the product, architecture, delivery roadmap, and engineering workflows.

## Project status

Documentation foundation only. The application, dependency manifests, database, and deployment have not been created. Requirements describe intended behavior, not implemented features.

## Technology direction

- PHP / Laravel: authentication, authorization, validation, business logic, APIs, and agent tools.
- Vue 3 / TypeScript: editing, portfolio presentation, and conversational interfaces.
- PostgreSQL: structured career records, publication snapshots, and references.
- Model provider adapter: bounded tool calling and structured answers through a server-side integration.
- Deployment: a configurable application runtime, persistent PostgreSQL, HTTPS delivery, and recoverable releases.

Exact runtime versions and shared implementation choices will be recorded during implementation. Actual hosting services, model subscriptions, and environment settings belong to local operations documentation. See [Deployment contract](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md).

## Public and local documentation

Git-tracked root documents, `.github/`, and `design/` form the shared documentation intended for GitHub: requirements, technology choices, architecture, implementation contracts, engineering standards, reusable development tasks, and UI/UX product design references.

`.local/docs/` is the ignored local companion for a particular demo: actual cloud/API services, environment restrictions, quotas, credential locations, and deployment troubleshooting. Start with its `README.md` when working on that environment. It is intentionally absent from a fresh clone; shared documentation must remain usable without it. Never put secret values in either documentation set. See [Documentation](.github/Guidelines/Documentation.md) for the ownership rules.

## Start here

- [Project objective and scope](Project.md)
- [AI agent entry point](AGENTS.md)
- [Repository instructions](.github/copilot-instructions.md)
- [Knowledge base and functional specifications](.github/KnowledgeBase/Index.md)
- [Implementation backlog](.github/TaskLogs/Copilot_Scrum.md)
- [Documentation provenance](.github/KnowledgeBase/Sources.md)

## Product design references

The UI/UX suite shows how the requirements translate into a private contribution editor, an explicit publication preview, and a public portfolio with source-cited answers. These are design references with fictional data, not implemented application screens.

![Public portfolio and source-cited question answering design](design/2026-11-17/mockups/03-public-portfolio.png)

- [Design pack: three UI mockups and four UX flows](design/2026-11-17/README.md)
- [UI framework, screen specifications, and requirement mapping](.github/KnowledgeBase/KB_CareerAtlas_Design_UIUX.md)
- [Page states and interaction requirements](.github/KnowledgeBase/KB_CareerAtlas_Design_UserExperience.md)

The static `design/2026-11-17/index.html` gallery can be opened after cloning. The Markdown design pack is the GitHub-readable entry point; publishing these files does not deploy a website.

## Documentation workflow

This repository adapts GacUI's documentation organization: root instructions, a knowledge-base index, focused guidelines, workflow prompts, task logs, and learning records. All working documentation is in English.

Typical workflow commands for a coding assistant:

```text
ask Explain the publication and revocation rules.
design Plan the first P0 implementation slice.
plan Break the design into implementation steps and tests.
summarize Prepare the execution checklist.
execute and verify
kb Record the implemented design and verified commands.
```

Natural-language requests also work. A request to write documentation does not authorize application implementation or deployment.

## Validate documentation

Run from PowerShell:

```powershell
& './.github/Scripts/Validate-Documentation.ps1'
```

Run from the repository root. The script checks local Markdown links, required files, requirement identifiers, workflow markers, the public/local documentation boundary, and an English-baseline character guard. It does not check external URLs, heading anchors, or application behavior.
