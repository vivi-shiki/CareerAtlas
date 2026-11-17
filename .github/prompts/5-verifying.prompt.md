# Verifying

## Goal

Establish what the current change actually satisfies.

## Procedure

1. Compare the changed files with the task scope and relevant acceptance criteria.
2. Run the checks supported by the repository, using [Building](../Guidelines/Building.md) and [Testing](../Guidelines/Testing.md).
3. Inspect failures, fix issues within scope, and rerun affected checks.
4. Distinguish documentation checks, application tests, simulated-provider tests, and live AI evaluation.
5. Record commands, outcomes, scope, and limitations in [Copilot_Execution.md](../TaskLogs/Copilot_Execution.md).

## Output

A verification report. Add `# !!!VERIFIED!!!` only with named passing checks and the precise scope they verify. Missing credentials, skipped tests, and absent application code remain explicit limitations.
