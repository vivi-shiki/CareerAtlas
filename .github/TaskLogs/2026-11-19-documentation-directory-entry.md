# Restore documentation directory entry URLs

## Finding and fix

The public language directory URL returned HTTP 403 while its explicit `index.html` returned HTTP 200. The host template declared only `index.php`, so Nginx found the directory but could not serve its static index. Previous documentation crawling covered explicit filenames and missed this server-specific failure.

The bootstrap template now includes `index.html` after `index.php`. The existing host requires the matching operator configuration update, syntax validation, and reload; an application deployment alone does not replace host configuration.

A public HTTP regression check compares the documentation root and each localized directory against their canonical HTML files. It covers 16 entry URLs, checks page identity and locale, and rejects redirects away from the application origin. The deployment workflow runs it after verifying the public release revision.

## Verification

- The new regression check reproduced HTTP 403 before the configuration update and passed all 16 entry URLs afterward.
- The existing host configuration was backed up, updated, validated with `nginx -t`, and reloaded successfully. Concrete host details and the backup path remain in the ignored operations log.
- The supplied Japanese directory URL rendered the guide in the in-app browser. Clicking the quick-start sidebar link opened its expected chapter and content.
- Unrelated case-library/importer edits were preserved and excluded.

## Status

FINISHED: directory entry configuration corrected and automatic public URL checks added.

VERIFIED: live entry URL checks and the browser navigation described above. The subsequent repository release is recorded in the local operations log.
