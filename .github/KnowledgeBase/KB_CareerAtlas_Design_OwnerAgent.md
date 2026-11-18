# Owner Assistance Design and Evaluation Contract

This design covers capture, source relevance mapping, prepared answers, opportunity introductions, practice feedback and export translation. It extends the existing application-owned tool loop; it does not give the public portfolio assistant owner tools.

## Provider protocol research

The [official function-calling guide](https://ai.google.dev/gemini-api/docs/function-calling) describes model-proposed calls executed by the application. The [GenerateContent reference](https://ai.google.dev/api/generate-content) defines structured function responses and matching call identifiers. Retain the complete returned model content in the next request so opaque metadata is preserved. Validate every proposed tool and output locally; a model call does not grant authorization. Actual model identifiers and availability remain local configuration.

## Run contract

Authenticated operations start, inspect, cancel and explicitly apply a run. Each request has an idempotency UUID, purpose, owned subject and expected version. Allow only explicitly selected sources, exclude private notes, and fingerprint the current subject and source versions. Check the fingerprint before remote calls, on completion, on reads and before applying. Changed or withdrawn sources invalidate the proposal. Concurrent owner mutations share the existing owner lock; remote calls do not hold it.

Use a separate, default-disabled owner allowlist. The deterministic transport is available only in testing. Each run is asynchronous, limited to a 45-second deadline, bounded input and output, at most three model calls and one outstanding run per owner. Cancellation stops subsequent calls and discards late output. No automatic provider retry or paid fallback. Expired runs are pruned; explicitly applied domain records remain.

Only two tools are exposed: read the selected source bundle and finish a purpose-specific proposal. Career citations must use retrieved handles and exact excerpts. Requirements and user notes are data, not instructions. There is no arbitrary URL fetch, SQL, public publishing or owner-tool access in this model loop.

## Purpose-specific outputs

| Purpose | Context | Review/application |
| --- | --- | --- |
| Capture | Submitted notes and selected project context | Editable unknown-blank draft, suggested skills and at most two optional questions; applying only saves the capture draft |
| Mapping | Requirements and explicitly selected confirmed records | Requirement-to-source rationale, evidence gaps and at most two optional questions; owner chooses final records |
| Answer | Preparation brief, question, selected confirmed records and length | Short/standard draft with citations; explicit application creates an unconfirmed answer version |
| Introduction | Opportunity emphasis and only the fields selected for its public preview | Draft without new career claims; application never confirms or publishes it |
| Feedback | Current practice question/answer and selected sources | Specificity, structure, consistency and clarity; no score, hiring prediction or employer judgment; explicit retain only |
| Export | Current authorized publication fields and target language | Exact field-key set; preserve names, numbers, dates, URLs and responsibilities; reviewed download rechecks source digest |

Capture and preparation follow-ups are optional. The owner can skip, answer in the editable notes/question, and explicitly rerun. Missing evidence is an information gap, never proof of missing ability. Source labels must distinguish personal actions, team outcomes and self-reported evidence.

## Deterministic and live acceptance

Before implementation, test for: valid six-purpose output; unknown/foreign subject; idempotent request and mismatched retry; disabled provider; rate and outstanding-run bounds; cancellation before/during calls; deadline; provider failure; invalid schema; fabricated citations; more than two questions; changed/deleted source during generation; stale application; no private-note context; selected-field introduction and export isolation; explicit apply without confirmation/publication; practice retention; translation exact field set.

Then evaluate fictional cases against the real provider in all three languages. Review source attribution, unknown facts, team versus personal contribution, translated names/numbers, injected instructions, evidence gaps and four feedback dimensions. Retain initial failures and explicit retries. Live model evaluation is separate from deterministic orchestration tests and does not prove universal factual correctness.
