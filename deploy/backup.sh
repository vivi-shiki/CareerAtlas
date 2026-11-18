#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
destination=/var/backups/careeratlas
install -d -m 0700 "$destination"
stamp=$(date -u +%Y%m%dT%H%M%SZ)
runuser -u postgres -- pg_dump -Fc careeratlas > "$destination/$stamp.dump.tmp"
pg_restore --list "$destination/$stamp.dump.tmp" >/dev/null
mv "$destination/$stamp.dump.tmp" "$destination/$stamp.dump"
# Keep 14 days of local recovery points. VM snapshots provide a separate recovery layer.
find "$destination" -maxdepth 1 -type f -name '*.dump' -mtime +14 -delete
echo 'Database backup completed.'
