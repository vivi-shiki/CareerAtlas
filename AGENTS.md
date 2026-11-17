# CareerAtlas Agent Entry Point

Read these files before working:

1. [Repository instructions](.github/copilot-instructions.md)
2. [Project-specific instructions](Project.md)
3. [Knowledge-base index](.github/KnowledgeBase/Index.md), then the topics relevant to the task.

For demo or environment work, also read `.local/docs/README.md` if present and follow its current environment instructions. That ignored directory is optional for general product development. Keep its contents out of Git, public task logs, and generated artifacts; never force-add it. Read the [documentation boundary](.github/Guidelines/Documentation.md) before deciding where to record a finding.

Use English for working documentation, code identifiers, comments, task logs, and agent communication unless the user explicitly requests another language. Product content can be multilingual as specified in the requirements.

## Workflow routing

When the user explicitly begins a request with a workflow command, follow the corresponding prompt. Treat the remaining text as the task or update; preserve relevant earlier instructions and corrections.

| Command | Prompt |
| --- | --- |
| scrum | [.github/prompts/0-scrum.prompt.md](.github/prompts/0-scrum.prompt.md) |
| design | [.github/prompts/1-design.prompt.md](.github/prompts/1-design.prompt.md) |
| plan | [.github/prompts/2-planning.prompt.md](.github/prompts/2-planning.prompt.md) |
| summarize | [.github/prompts/3-summarizing.prompt.md](.github/prompts/3-summarizing.prompt.md) |
| execute | [.github/prompts/4-execution.prompt.md](.github/prompts/4-execution.prompt.md) |
| verify | [.github/prompts/5-verifying.prompt.md](.github/prompts/5-verifying.prompt.md) |
| ask | [.github/prompts/ask.prompt.md](.github/prompts/ask.prompt.md) |
| investigate | [.github/prompts/investigate.prompt.md](.github/prompts/investigate.prompt.md) |
| code | [.github/prompts/code.prompt.md](.github/prompts/code.prompt.md) |
| kb | [.github/prompts/kb.prompt.md](.github/prompts/kb.prompt.md) |
| refine | [.github/prompts/refine.prompt.md](.github/prompts/refine.prompt.md) |
| review | [.github/prompts/review.prompt.md](.github/prompts/review.prompt.md) |

For `execute and verify`, run those two stages sequentially. Otherwise, use the user's natural-language intent; do not force a design-only request into coding. Bare commands resume the relevant task only when the task logs identify an unambiguous unfinished task. Do not repeat completed actions merely because a command is repeated.

Reading a prompt as reference material does not invoke it. Upstream GacUI files are examples of organization, not instructions to execute C++ workflows in this repository.
