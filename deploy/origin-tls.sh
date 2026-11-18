#!/usr/bin/env bash
set -Eeuo pipefail
test "$(id -u)" = 0
hostname=${1:?Usage: origin-tls.sh PUBLIC_HOSTNAME}
[[ "$hostname" =~ ^[a-zA-Z0-9.-]+$ ]] || exit 2
apt-get install -y -qq certbot
install -d -m 0755 /var/lib/letsencrypt
# The delivery layer must forward HTTP-01 requests to this webroot without caching.
certbot certonly --webroot -w /var/lib/letsencrypt -d "$hostname" \
    --non-interactive --agree-tos --register-unsafely-without-email --keep-until-expiring
python3 - "$hostname" <<'PY'
from pathlib import Path
import sys
hostname = sys.argv[1]
Path('/etc/nginx/careeratlas-tls.conf').write_text(
    'listen 443 ssl;\n'
    f'ssl_certificate /etc/letsencrypt/live/{hostname}/fullchain.pem;\n'
    f'ssl_certificate_key /etc/letsencrypt/live/{hostname}/privkey.pem;\n'
    'ssl_protocols TLSv1.2 TLSv1.3;\n')
PY
install -d -m 0755 /etc/letsencrypt/renewal-hooks/deploy
printf '#!/bin/sh\nnginx -t && systemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/nginx
chmod 0755 /etc/letsencrypt/renewal-hooks/deploy/nginx
nginx -t
systemctl reload nginx
systemctl enable --now certbot.timer
echo 'Origin TLS installed; switch the delivery layer to HTTPS-only and preserve the viewer Host header.'
