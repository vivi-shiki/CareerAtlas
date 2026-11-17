# Documentation

## Language and structure

Use English prose, titles, examples, prompts, logs, and comments. Product content may be multilingual; the working-language rule does not require translating users' career records. Keep filenames and links consistent with the GacUI-style structure.

Place durable contracts in `KnowledgeBase/KB_CareerAtlas_*.md`, design explanations in `KB_CareerAtlas_Design_*.md`, and navigation in [Index.md](../KnowledgeBase/Index.md). Use Guidelines for working procedures, prompts for task workflows, and TaskLogs for dated evidence. Link to a canonical requirement rather than duplicating it across files.

## Claims and traceability

Distinguish confirmed requirements, proposed designs, open choices, and observed implementation. Preserve feature and acceptance IDs. Record intentional scope changes and the user's instruction that caused them. Do not invent framework versions, benchmark results, company requirements, or successful commands.

Use relative Markdown links inside the repository. Before finishing, check links and IDs with the validator and review changed content for contradictory permissions, phase assignments, or completion claims.

## Task records

Maintain the GacUI marker headings. Archive a completed task's logs together before reusing the current filenames. Preserve unfinished work and explicit corrections. `FINISHED` means authorized work is done; `VERIFIED` must name what actually passed. Neither marker means a planned future application exists.

## Public and local ownership

| Information | Location |
| --- | --- |
| Product requirements, feature IDs, priorities, acceptance criteria | Git-tracked KnowledgeBase and shared backlog |
| Technology stack, versions, architecture, data/API/agent contracts, implementation details | Git-tracked project/design documents and source code |
| Coding, testing, security, release standards; reusable development issues and fixes | Git-tracked Guidelines, task logs, and learning records |
| UI/UX specifications, mockups, user flows, design sources and fictional demo references | Git-tracked KnowledgeBase and `design/` |
| Actual cloud/API services, selected models, projects, regions, quotas, costs, resource names | Ignored `.local/docs/` |
| Machine setup, credential locations, demo-specific commands and troubleshooting | Ignored `.local/docs/` |
| Secret values, access tokens, private keys | Credential storage only; neither documentation set |

The local companion starts at `.local/docs/README.md`. It is intentionally missing in fresh clones. Mention its conventional path as code, not a Markdown link from shared docs; all public documentation links must resolve without local files. Create local notes only when needed and use English. Read local constraints before operating an existing environment; do not guess missing credentials or environment values.

Git ignores `.local/`, real environment files, and common credential material. Sanitized `.env.example` files may be tracked after review. Ignore rules are an accidental-staging guard, not access control or encryption. Never force-add private files. Do not attach the local folder to releases, build artifacts, or published documentation.

Before a commit or publication, inspect `git status --short`, `git diff --cached`, and `git diff --cached --check`; run the documentation validator. Keep identifiers, credential paths, and private operational evidence out of shared logs. If a demo problem reveals a general defect, record its sanitized reproduction and reusable fix in Git, retaining concrete environment evidence locally.

Moving a file or adding an ignore rule does not remove prior Git history. This separation changes the current tree; history rewriting and remote publication are separate operations.
