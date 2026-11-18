# Troubleshooting

| Symptom | Check and recovery |
| --- | --- |
| Workspace opens sign-in | Your session is missing or expired. Sign in again; the homepage/docs remain public. |
| Record cannot be confirmed | Complete title, project, type, occurrence, and personal action; review field errors. |
| Record missing from Publish | Confirm the current draft and check that it is still available. |
| Save says the source changed | Another edit won the version check. Keep a copy of unsaved text, reload, compare, and reapply deliberately. |
| Publish asks for another preview | Sources or selected fields changed. Generate and review a fresh preview. |
| Public page is unavailable | The owner may have revoked it or withdrawn a source. Do not try to bypass access through an old assistant conversation. |
| AI controls are disabled | The operator may have disabled the provider or excluded this owner/publication. Continue manually. |
| AI keeps waiting locally | Check the `agent` queue worker, provider configuration, and server logs. Do not expose keys in bug reports. |
| Practice disappeared | Only explicitly kept sessions persist beyond the 24-hour temporary period. |
| UI language changed but text did not | Saved records and old answers retain their authored language. Select target content language for new drafts/exports. |
| Translation download is disabled | Review the translated fields and check the review acknowledgment. |
| A list omits older P1 items | Current P1 lists show the latest 100 owned records. |
| Local docs return 404 | Run `npm run build` and open the explicit `/docs/<locale>/index.html` URL. |

## Report an issue

Include the affected workflow, UI/content language, browser, reproducible steps, expected result, and observed behavior. Use a fictional example or redact the screenshot. Never attach `.env`, bearer tokens, real contact details, private career records, or provider secrets.

For development failures, include the command and sanitized error. Start with [local setup](./development.md) and [architecture](./architecture.md) to locate the relevant process.
