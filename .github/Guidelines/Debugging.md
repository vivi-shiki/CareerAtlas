# Debugging

## Evidence-first investigation

Record the expected behavior and smallest known reproduction. Inspect relevant code, configuration, logs, database state, and recent changes. Keep observed facts separate from hypotheses. Avoid logging private prompts, source content, tokens, or credentials.

Trace failures through the request boundary, authorization scope, domain operation, persistence, and response. For AI failures, also inspect selected tool names, scoped source IDs/versions, validation failures, and timeout/budget outcomes using redacted diagnostics.

## Fix and verification

Correct the responsible layer and reproduce the original case again. Include a regression check when the failure can recur and the check is meaningful. Preserve the failing evidence and final command in [the investigation log](../TaskLogs/Copilot_Investigate.md); link the implementation result from the execution log.

If a dependency or credential is unavailable, state what could and could not be verified. Do not substitute a fabricated successful run. Escalate only the missing information or authorization actually needed to continue.
