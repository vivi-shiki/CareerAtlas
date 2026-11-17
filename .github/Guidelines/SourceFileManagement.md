# Source File Management

## Before editing

Read the root instructions, relevant knowledge-base topics, and the actual target files. Inspect `git status --short` and relevant diffs. Preserve unrelated and concurrent changes. Use focused searches instead of reading the entire repository indiscriminately.

## Application boundaries

Laravel owns domain rules, validation, access checks, and transactions. Vue owns presentation and interaction. Do not encode the only copy of an authorization rule in the UI. Keep secrets and provider credentials server-side.

After scaffolding, follow the framework's existing layout and record its paths in [Project.md](../../Project.md). Change manifests and source definitions rather than vendor packages, generated files, or build output. Avoid speculative layers and dependencies without a requirement.

## Data and changes

Use fictional fixtures with visible labels. Never seed private career or employer data by searching the owner's other workspaces. Schema changes need an explicit migration and a recovery strategy appropriate to the data at risk. Do not reset, force checkout, recursively clean, publish, or commit as a routine documentation step.

Keep a change aligned with its feature and acceptance IDs. When behavior changes, update the relevant contract and tests in the same task. Preserve IDs or document their replacement and rationale; do not silently renumber the baseline.
