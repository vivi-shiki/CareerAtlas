# AI Agent for your career stories

CareerAtlas's **AI Agent** helps you turn notes into experience drafts, prepare source-based interview answers, and answer visitors' portfolio questions with citations. Projects, personal contributions, skills, and evidence provide the facts behind the conversation.

**Stack:** PHP 8.3+ (**Laravel 13 backend framework**) · Vue 3 · TypeScript · PostgreSQL.

**[Start with the AI Agent](./ai-agent.md)** · **[First-time setup](./quickstart.md)** · **[Run locally](./development.md)**

## Two ways to use the Agent

For a technical review, start with the [Laravel backend and source map](./backend.md), [data model and publication lifecycle](./data-model.md), and [testing procedures and evidence](./testing.md). These chapters connect the product behavior to its implementation.

- **For you:** capture editable drafts, map requirements to evidence, prepare answers, review practice, and tailor introductions or translations.
- **For visitors:** explore a published portfolio through Q&A and open citations to inspect the selected source context.

**You choose the sources and review the result.** The Agent does not confirm or publish for you. AI availability depends on the operator; manual workflows remain available.

![AI Agent answering with a source citation](../assets/screenshots/en/qa.jpg)

*Actual application UI with fictional data. The pictured answer uses a deterministic test provider to demonstrate citations, not live model quality.*

## Choose your task

| I want to… | Start here |
| --- | --- |
| Try the AI Agent from notes to cited Q&A | [AI Agent walkthrough](./ai-agent.md) |
| Document what I did | [Projects, experience, skills, and evidence](./records.md) |
| Turn scattered notes into a record | [Capture and revision history](./capture.md) |
| Prepare and rehearse an interview answer | [Preparation and written practice](./preparation.md) |
| Share a portfolio and answer visitor questions | [Publishing and Q&A](./publishing.md) |
| Tailor a page for one opportunity | [Opportunities and Markdown export](./opportunities.md) |
| Understand what becomes public | [Privacy and AI boundaries](./privacy-and-ai.md) |
| Extend or self-host the application | [Architecture](./architecture.md), [development](./development.md), [API and MCP](./mcp.md) |

## Three languages, one workflow

The application and this guide support English, Simplified Chinese, and Japanese. The language menu switches the current documentation chapter. Product UI language, profile language, and generated-content target language are separate settings. Changing the UI does not translate saved records or previous answers.

The guide covers implemented P0/P1 functionality. Named-recipient grants, organization collaboration, and reviewer confirmation are [planned P2 features](./roadmap.md), not currently available controls. AI availability depends on the operator; manual workflows remain usable without it.
