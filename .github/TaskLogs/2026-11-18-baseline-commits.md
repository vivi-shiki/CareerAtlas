# P0 Baseline Commit Sequence

The user requested clear explanations and staged commits before P1 development. The working tree contained the complete P0 application, its acceptance suite, and localization; these were not previously committed.

| Commit | Scope | Reason |
| --- | --- | --- |
| `157fc2b` | Application runtime, owner/public workflows, bounded Agent, MCP domain tools, localization | Establish the executable product with its dependency lockfiles and privacy boundary |
| `cc0d4bd` | PostgreSQL tests, browser tests, model doubles, evaluation and MCP scripts | Keep acceptance tooling and fictional fixtures reviewable as a separate change |
| This documentation commit | Product/development documentation, three READMEs, implementation and localization acceptance records | Explain supported behavior, reproducible commands, evidence, and remaining scope |

This is a sequence of dependent commits forming one accepted baseline. The application commit includes localization because those source changes already form the current integrated implementation; the history does not pretend that an earlier English-only application was separately committed.

Accepted evidence: 70 PostgreSQL tests with 872 assertions; 71 distinct browser cases (70-case regression followed by the expanded 10-case localization suite); 41 MCP tools and 26 workflow calls; passing build, type, catalog, and documentation checks. The [P0 ledger](2026-11-18-p0-implementation.md) and [localization record](2026-11-18-localization.md) distinguish synthetic model doubles from live evaluation and describe visual checks and limitations.

Actual credentials, environment settings, provider subscriptions, local reports, dependency directories, and generated assets remain excluded. These commits do not deploy an application or publish a Git branch. P1 work begins after this baseline and follows the existing ten-gate workflow.
