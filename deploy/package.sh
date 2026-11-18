#!/usr/bin/env bash
set -Eeuo pipefail
# Run after the production dependency install and frontend build in an isolated checkout.
revision=${1:?Usage: package.sh COMMIT OUTPUT}
output=${2:?Usage: package.sh COMMIT OUTPUT}
[[ "$revision" =~ ^[0-9a-f]{40}$ ]] || exit 2
test -f public/build/manifest.json
test -f vendor/autoload.php
test ! -e public/hot
printf '{"revision":"%s"}\n' "$revision" > public/deployment.json
tar --exclude='bootstrap/cache/*.php' --exclude='database/*.sqlite*' \
  -czf "$output" app artisan bootstrap config contracts database public resources routes \
  vendor composer.json composer.lock
echo "Packaged revision $revision"
