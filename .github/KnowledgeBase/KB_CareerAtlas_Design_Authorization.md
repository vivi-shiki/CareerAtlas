# CareerAtlas Authorization and Publication

## Access model

Every request binds the current principal and permitted scope on the server. Models cannot choose another user, tenant, recipient, or publication by supplying an ID. Valid identifiers still require object authorization.

P0 supports private owner data and selected public snapshot fields. P2 adds authenticated named grants and organization scopes; membership alone does not expose personal records. Administrator capabilities do not imply unrestricted content access.

## Publication rules

1. Select confirmed source records and an explicit field allowlist.
2. Preview exactly what recipients will receive, including evidence labels and contact links.
3. Create an immutable snapshot in a transaction after the owner's explicit publication action.
4. Serve pages, counts, skills, search, and Q&A from that authorized snapshot.
5. Source editing does not silently change the snapshot.
6. Republish only after review of updated content.
7. Revoke access synchronously across public endpoints and new agent responses.

## Revocation and derived data

Publication revocation disables its pages and Q&A. Source deletion or outward grant revocation disables dependent references; never serve old snapshots as a bypass. Clear or invalidate retrieval caches and access-scoped response caches. Recheck authorization before returning an in-flight answer.

Old server-stored conversation turns and retrieved context must be reauthorized or excluded from subsequent model requests after revocation. Do not rely solely on filtering the next search result: prior conversation text may contain the revoked content.

Already downloaded or client-displayed content cannot be recalled. Explain that boundary when introducing export or sharing controls.

## Privacy-preserving behavior

Private titles, counts, snippets, skills, source URLs, and existence must not leak through summaries or error messages. Select a consistent unauthorized-object response policy during implementation. Test pages, APIs, citations, search, chat, and export independently.

Owner interview preparation, private notes, organization material, opportunity names, and unrelated visitor conversations do not enter public model context. Use synthetic evaluation records; do not copy actual private résumés into seeds.

## Organization lifecycle

Joining creates membership, not blanket sharing. Leaving or suspension revokes organization access immediately. Existing personal records remain personal; organization records are not automatically exported to the departing employee. Review comments do not overwrite an employee's source statement.

## Agent instruction boundary

Pasted job descriptions, project text, and evidence excerpts are untrusted data. Tool results cannot expand tool permissions or instruct the agent to reveal other scopes. Application checks enforce the boundary independently of model compliance.

Related acceptance: AC-S1-01, AC-S1-05, AC-S4-02, AC-S4-04, AC-S5-01 through AC-S5-06 in [Testing](KB_CareerAtlas_Testing.md).
