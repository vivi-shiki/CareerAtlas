# CareerAtlas

**An AI-powered career portfolio and skills knowledge system that connects personal experience, project demonstrations, contributions, and evidence, and makes authorized records searchable through conversation.**

CareerAtlas is a standalone product for individuals and organizations. It helps people document their work, present their capabilities, and prepare for opportunities. Future organization features help employees share experience and support development conversations. These documents define the product, architecture, delivery roadmap, and engineering workflows.

## Project status

Documentation foundation only. The application, dependency manifests, database, and deployment have not been created. Requirements describe intended behavior, not implemented features.

## Technology direction

- PHP / Laravel: authentication, authorization, validation, business logic, APIs, and agent tools.
- Vue 3 / TypeScript: editing, portfolio presentation, and conversational interfaces.
- PostgreSQL: structured career records, publication snapshots, and references.
- Model provider adapter: bounded tool calling and structured answers; provider selection remains open.

- AWS: selected deployment platform, using the new AWS experience. The configured selected Region is `ap-southeast-2` and the CLI profile is `default`.

Exact runtime versions and AWS services will be selected and recorded during implementation. The hosting decision does not select an AI provider. See [AWS deployment](.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md) for confirmed decisions, environment constraints, and remaining choices.

## Start here

- [Project objective and scope](Project.md)
- [AI agent entry point](AGENTS.md)
- [Repository instructions](.github/copilot-instructions.md)
- [Knowledge base and functional specifications](.github/KnowledgeBase/Index.md)
- [Implementation backlog](.github/TaskLogs/Copilot_Scrum.md)
- [Documentation provenance](.github/KnowledgeBase/Sources.md)

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
& 'E:\work\CareerAtlas\.github\Scripts\Validate-Documentation.ps1'
```

The script also works from another checkout location when invoked using that checkout's path. It checks local Markdown links, required files, requirement identifiers, workflow markers, and an English-baseline character guard. It does not check external URLs, heading anchors, or application behavior.
