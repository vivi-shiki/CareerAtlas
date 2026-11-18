#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
# SSH forced command: no interactive shell, forwarding, arbitrary paths or commands.
if [[ "${SSH_ORIGINAL_COMMAND:-}" =~ ^deploy\ ([0-9a-f]{40})$ ]]; then
    revision=${BASH_REMATCH[1]}
else
    echo 'Only deploy COMMIT is accepted.' >&2
    exit 2
fi
exec 9>/srv/careeratlas/incoming/receive.lock
flock -w 600 9
archive=/srv/careeratlas/incoming/release.tar.gz
trap 'rm -f "$archive"' EXIT
# A large or interrupted transfer cannot fill the server disk indefinitely.
head -c 209715201 > "$archive"
test "$(stat -c %s "$archive")" -le 209715200
sudo /usr/local/sbin/careeratlas-release "$revision"
