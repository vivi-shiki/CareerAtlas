# Review

## Goal

Find actionable correctness, regression, and maintainability issues in the requested changes.

## Procedure

Inspect the actual diff and surrounding contracts. Prioritize broken behavior, privacy/authorization failures, data loss, and missing meaningful verification. Check phase scope and distinguish executable code from proposed designs. Trace each finding to concrete evidence, affected behavior, and a location.

Report findings in severity order. Avoid speculative claims and cosmetic findings without a project requirement. State when no actionable findings were found and identify relevant coverage limits. Do not silently implement fixes in a review-only task.

## Output

Concise findings with impact, evidence, and suggested correction; include verification status.
