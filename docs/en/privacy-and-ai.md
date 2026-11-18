# Privacy and AI

## What can each reader see?

| Context | Available material |
| --- | --- |
| Signed-in owner | Their own profile, drafts, notes, records, preparation, and opportunities |
| Public visitor | Only selected fields from that publication's authorized snapshot |
| Public assistant | The same page-scoped sources, not the private archive |
| Owner assistance | Submitted input and explicitly selected permitted project/source context for that purpose |
| Developer MCP client | Authenticated owner's operations, constrained by token ability and server validation |

Private notes are excluded from public projections and owner AI source context by default. Evidence URLs and requirement text are untrusted data, not instructions that can grant access. The server enforces ownership and publication scope; hiding a button is not the security boundary.

## Review before applying

AI can propose capture drafts, requirement mapping, answers, introductions, practice feedback, and export translations. It cannot confirm an experience or answer, publish a page, or send an application on your behalf. You decide what to apply, retain, confirm, and publish.

Review citations and missing facts. Generated wording is not independent verification. The interface language and content language are separate; a translation cannot change facts or permissions.

## Availability and retention

AI requires server configuration, provider credentials, and separate owner/public availability controls. Operators should enable it deliberately for the intended scope. API keys never belong in browser code or the repository.

Manual editing, preview, publication, and browsing remain usable when AI is off or unavailable. Requests are bounded and cancellable. Unretained practice expires after 24 hours; proposal runs are cleaned up after one day. Operators must run the scheduler for cleanup.

Revoke an online page to withdraw its access. Source withdrawal is rechecked for public reading and Q&A. Copies that visitors already downloaded or saved remain outside the application's control. [Recipient grants and organization collaboration](./roadmap.md) are future work.
