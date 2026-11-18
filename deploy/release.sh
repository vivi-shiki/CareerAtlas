#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
revision=${1:?Missing revision}
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || exit 2
base=/srv/careeratlas
exec 8>/run/lock/careeratlas-release.lock
flock -w 600 8
release="$base/releases/$revision-$(date -u +%Y%m%d%H%M%S)"
previous=$(readlink -f "$base/current" || true)
maintenance=0
switched=0
app() { runuser -u www-data -- php "$release/artisan" "$@"; }
recover() {
    status=$?
    trap - EXIT
    if (( status != 0 )); then
        echo 'Release failed. Schema changes are never automatically reversed.' >&2
        if (( switched )) && [[ -n "$previous" && -d "$previous" ]]; then
            ln -s "$previous" "$base/current.recover"
            mv -Tf "$base/current.recover" "$base/current"
            systemctl reload php8.3-fpm
        fi
        if [[ -n "$previous" && -f "$previous/artisan" ]]; then
            runuser -u www-data -- php "$previous/artisan" up
            systemctl start careeratlas-worker careeratlas-scheduler.timer
        fi
    fi
    exit "$status"
}
trap recover EXIT
install -d -o careeratlas-deploy -g www-data -m 0750 "$release"
runuser -u careeratlas-deploy -- python3 /usr/local/lib/careeratlas/extract.py \
    "$base/incoming/release.tar.gz" "$release"
test -f "$release/vendor/autoload.php"
test -f "$release/public/build/manifest.json"
test -f "$release/artisan"
test "$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["revision"])' "$release/public/deployment.json")" = "$revision"
# Release code is immutable to both the web process and the SSH deployment principal.
chown -R root:www-data "$release"
chmod -R u=rwX,g=rX,o= "$release"
ln -s "$base/shared/.env" "$release/.env"
ln -s "$base/shared/storage" "$release/storage"
chown www-data:www-data "$release/bootstrap/cache"
chmod 0750 "$release/bootstrap/cache"
app config:cache
app view:cache
if [[ -n "$previous" && -f "$previous/artisan" ]]; then
    runuser -u www-data -- php "$previous/artisan" down --retry=30
    maintenance=1
fi
systemctl stop careeratlas-scheduler.timer
systemctl stop careeratlas-scheduler.service || true
systemctl stop careeratlas-worker
# Workers finish in-flight jobs before the backup. Store backups outside every release.
/usr/local/sbin/careeratlas-backup
app migrate --force
app deploy:check
ln -s "$release" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
switched=1
systemctl reload php8.3-fpm
app up
systemctl start careeratlas-worker careeratlas-scheduler.timer
curl --fail --silent --show-error --retry 5 --retry-delay 2 http://127.0.0.1/up >/dev/null
test "$(curl -fsS http://127.0.0.1/deployment.json | python3 -c 'import json,sys; print(json.load(sys.stdin)["revision"])')" = "$revision"
systemctl is-active --quiet careeratlas-worker
echo "Deployed $revision"
