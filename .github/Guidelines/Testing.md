# Testing

## Documentation

Run the validator described in [Building](Building.md). It checks required files, local links, English-language consistency, code-fence balance, baseline ID coverage, and task-log markers. Review semantics separately, especially phase boundaries and claims of implemented behavior.

## Application work

Use [Acceptance and Testing](../KnowledgeBase/KB_CareerAtlas_Testing.md) to choose checks for the changed slice. Record feature IDs, acceptance IDs, relevant T-series cases, prerequisites, command, result, and limitations in the execution log.

- Test server authorization with both allowed and forbidden access, including guessed IDs and foreign sessions.
- Test snapshot publication, source updates, revocation, citations, and cached/chat-context access together.
- Use PostgreSQL integration checks for transactions, constraints, filtering, and version behavior.
- Exercise the owner-to-public happy path and its meaningful error states in the browser once a UI exists.
- Use deterministic provider doubles for tool orchestration and adversarial input. Label live model evaluation separately.
- Verify the non-AI fallback when provider calls fail or exceed their budget.

Do not add tests that merely restate low-impact text edits. Run required and risk-appropriate checks, and broaden them when failures or changed behavior warrant it. Never call a blocked, skipped, mocked, or unavailable check passed. Passing a fixed AI evaluation set does not prove universal correctness.

## Release verification

After infrastructure and release tooling exist, run the P0 workflow in the deployed environment. Verify authentication, persistent PostgreSQL data, public field filtering, citation access, and revocation through any delivery/cache layer. Exercise provider failure and the documented recovery procedure. Record the environment identity and detailed outcomes in ignored local logs. Record reusable checks, artifact revision, and sanitized product-level findings in shared logs.

Deployment smoke checks supplement application and authorization tests. No application release has been tested yet. Follow [Deployment](../KnowledgeBase/KB_CareerAtlas_Design_Deployment.md) for shared release requirements and the local companion for environment constraints.
