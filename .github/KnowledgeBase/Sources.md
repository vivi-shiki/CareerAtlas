# Sources and Documentation Adaptation

## Product source

The product baseline is the CareerAtlas functional specification, version 0.1, dated 2026-11-17. Its SHA-256 at import was `F62E309702670746B1F432E6D163FA53A1B4FF93C79D93E5175323DDF0159BBC`.

This repository contains an English development-oriented rendering of that specification. All 39 feature IDs and 27 acceptance-criterion IDs are retained. Narrative flows have been expanded into proposed engineering contracts. Those elaborations are design proposals, not verified implementation behavior.

The repository is the product and development documentation for CareerAtlas as a standalone system. Its scope covers recurring personal and organizational use. Examples and fixtures should use clearly labeled synthetic data.

## Documentation format reference

The reference is the official [GacUI repository](https://github.com/vczh-libraries/GacUI), inspected through the local checkout at `E:\work\GacUI` at commit `2855659caecbbd97d846aee92c02a4601dd0d8f6`.

Reference entry points at that revision:

- [Agent entry point](https://github.com/vczh-libraries/GacUI/blob/2855659caecbbd97d846aee92c02a4601dd0d8f6/AGENTS.md)
- [Project instructions](https://github.com/vczh-libraries/GacUI/blob/2855659caecbbd97d846aee92c02a4601dd0d8f6/Project.md)
- [Copilot instructions](https://github.com/vczh-libraries/GacUI/blob/2855659caecbbd97d846aee92c02a4601dd0d8f6/.github/copilot-instructions.md)
- [Knowledge-base index](https://github.com/vczh-libraries/GacUI/blob/2855659caecbbd97d846aee92c02a4601dd0d8f6/.github/KnowledgeBase/Index.md)
- [Workflow prompts](https://github.com/vczh-libraries/GacUI/tree/2855659caecbbd97d846aee92c02a4601dd0d8f6/.github/prompts)

## Adaptation map

| GacUI convention | CareerAtlas adaptation |
| --- | --- |
| Root agent and project entry files | Shared English instructions for repository agents |
| `.github/copilot-instructions.md` | General rules, navigation, source and task handling |
| KnowledgeBase index and `KB_*` topics | Product, five scenarios, Laravel/Vue/PostgreSQL design, agent contracts |
| Guidelines | Source management, building, testing, debugging, documentation |
| Numbered design-to-verification prompts | Task-sized workflows with original stage names and marker headings |
| TaskLogs and Learning | Current task evidence, backlog, and observed lessons |
| Scripts | A repository-local documentation validator |

The explanatory content and procedures are authored for CareerAtlas. GacUI's C++ toolchain, build wrappers, project names, external repositories, and automation requirements are not applicable here. Referencing a workflow does not invoke it. Existing context and user edits are preserved; delegation, publishing, commits, and destructive cleanup are not implicit workflow steps.

## Decision status

Confirmed from the source: the product objective, five scenarios, feature priorities, acceptance criteria, and PHP/Laravel, Vue 3/TypeScript, PostgreSQL stack.

Confirmed by subsequent product-owner instructions on 2026-11-17: AWS is the deployment platform, and the documentation describes a standalone product. Supplied AWS environment: new AWS experience, CLI profile `default`, selected Region `ap-southeast-2`. These decisions supersede the original open hosting choice; live AWS state has not been verified.

Proposed in these documents: module boundaries, logical schema, endpoint shapes, retrieval contracts, agent execution limits, and detailed verification strategy.

Open until implementation: exact runtime/package versions, frontend integration style, AI provider/model, AWS service topology and delivery tooling, and concrete build/test/deployment commands. Inspect current official documentation when making those choices. The source specification is a planning document, not evidence of implementation.

AWS environment constraints and official references are recorded in [Deployment](KB_CareerAtlas_Design_Deployment.md).
