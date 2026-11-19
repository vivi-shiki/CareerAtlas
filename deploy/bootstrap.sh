#!/usr/bin/env bash
set -Eeuo pipefail
umask 027
# Run as root on a fresh Ubuntu 24.04 host. Inputs are installed separately, never baked into Git.
source_dir=${1:?Usage: bootstrap.sh DIRECTORY_WITH_DEPLOY_FILES}
test "$(id -u)" = 0
test -s /etc/careeratlas/origin-token
test -s /etc/careeratlas/public-url
test -s /etc/careeratlas/deploy.pub
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq nginx postgresql postgresql-client php8.3-fpm php8.3-cli \
    php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl \
    php8.3-bcmath python3 curl unzip fail2ban
if ! id careeratlas-deploy >/dev/null 2>&1; then
    useradd --create-home --shell /bin/bash careeratlas-deploy
fi
base=/srv/careeratlas
install -d -m 0755 "$base" "$base/releases"
install -d -o careeratlas-deploy -g careeratlas-deploy -m 0700 "$base/incoming"
install -d -o root -g www-data -m 0750 "$base/shared"
install -d -o www-data -g www-data -m 0750 "$base/shared/storage" \
    "$base/shared/storage/app" "$base/shared/storage/app/private" \
    "$base/shared/storage/framework" "$base/shared/storage/framework/cache" \
    "$base/shared/storage/framework/cache/data" "$base/shared/storage/framework/sessions" \
    "$base/shared/storage/framework/views" "$base/shared/storage/logs"
if [[ ! -e "$base/shared/.env" ]]; then
    db_password=$(openssl rand -hex 32)
    app_key=$(openssl rand -base64 32)
    runuser -u postgres -- psql -v ON_ERROR_STOP=1 <<SQL
CREATE ROLE careeratlas LOGIN PASSWORD '$db_password';
CREATE DATABASE careeratlas OWNER careeratlas;
SQL
    cat > "$base/shared/.env" <<ENV
APP_NAME=CareerAtlas
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$app_key
APP_URL=$(cat /etc/careeratlas/public-url)
LOG_CHANNEL=daily
LOG_LEVEL=warning
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=careeratlas
DB_USERNAME=careeratlas
DB_PASSWORD=$db_password
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=database
AI_ENABLED=false
AI_DRIVER=gemini
AI_PUBLICATION_ALLOWLIST=
AI_OWNER_ALLOWLIST=
ENV
    chown root:www-data "$base/shared/.env"
    chmod 0640 "$base/shared/.env"
    unset db_password app_key
fi
install -d -m 0755 /usr/local/lib/careeratlas
install -m 0755 "$source_dir/extract.py" /usr/local/lib/careeratlas/extract.py
install -m 0755 "$source_dir/release.sh" /usr/local/sbin/careeratlas-release
install -m 0755 "$source_dir/backup.sh" /usr/local/sbin/careeratlas-backup
install -m 0755 "$source_dir/receive.sh" /usr/local/bin/careeratlas-receive
echo 'careeratlas-deploy ALL=(root) NOPASSWD: /usr/local/sbin/careeratlas-release' > /etc/sudoers.d/careeratlas
chmod 0440 /etc/sudoers.d/careeratlas
visudo -cf /etc/sudoers.d/careeratlas
install -d -o root -g root -m 0755 /home/careeratlas-deploy/.ssh
printf 'restrict,command="/usr/local/bin/careeratlas-receive" %s\n' "$(cat /etc/careeratlas/deploy.pub)" > /home/careeratlas-deploy/.ssh/authorized_keys
chmod 0644 /home/careeratlas-deploy/.ssh/authorized_keys
cat > /etc/ssh/sshd_config.d/60-careeratlas.conf <<'SSH'
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
Match User careeratlas-deploy
    AllowTcpForwarding no
    X11Forwarding no
    PermitTTY no
    ForceCommand /usr/local/bin/careeratlas-receive
SSH
sshd -t
systemctl reload ssh
cat > /etc/systemd/system/careeratlas-worker.service <<'UNIT'
[Unit]
Description=CareerAtlas agent queue worker
After=postgresql.service network.target
[Service]
User=www-data
Group=www-data
WorkingDirectory=/srv/careeratlas/current
ExecStart=/usr/bin/php artisan queue:work database --queue=agent --sleep=1 --tries=1 --timeout=55 --max-time=3600
Restart=always
RestartSec=5
TimeoutStopSec=70
KillSignal=SIGTERM
UMask=0027
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/srv/careeratlas/shared /srv/careeratlas/releases
[Install]
WantedBy=multi-user.target
UNIT
cat > /etc/systemd/system/careeratlas-scheduler.service <<'UNIT'
[Unit]
Description=CareerAtlas scheduled maintenance
[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/srv/careeratlas/current
ExecStart=/usr/bin/php artisan schedule:run
UMask=0027
NoNewPrivileges=true
UNIT
cat > /etc/systemd/system/careeratlas-scheduler.timer <<'UNIT'
[Unit]
Description=Run CareerAtlas scheduler each minute
[Timer]
OnCalendar=*-*-* *:*:00
Persistent=true
[Install]
WantedBy=timers.target
UNIT
cat > /etc/systemd/system/careeratlas-backup.service <<'UNIT'
[Unit]
Description=CareerAtlas daily PostgreSQL backup
[Service]
Type=oneshot
ExecStart=/usr/local/sbin/careeratlas-backup
UNIT
cat > /etc/systemd/system/careeratlas-backup.timer <<'UNIT'
[Unit]
Description=Daily CareerAtlas recovery point
[Timer]
OnCalendar=*-*-* 02:00:00 UTC
Persistent=true
RandomizedDelaySec=600
[Install]
WantedBy=timers.target
UNIT
cat > /etc/php/8.3/fpm/conf.d/99-careeratlas.ini <<'PHP'
expose_php=Off
display_errors=Off
log_errors=On
memory_limit=128M
opcache.enable=1
opcache.validate_timestamps=1
PHP
cat > /etc/nginx/conf.d/careeratlas-origin.conf <<'NGINX'
map_hash_bucket_size 128;
geo $realip_remote_addr $origin_network {
    default 0;
    127.0.0.1 1;
    include /etc/nginx/careeratlas-cloudfront-geo.conf;
}
map $http_x_careeratlas_origin $origin_token_valid {
    default 0;
    include /etc/nginx/careeratlas-origin-token.conf;
}
map "$realip_remote_addr:$origin_network:$origin_token_valid" $origin_allowed {
    default 0;
    ~^127\.0\.0\.1: 1;
    ~:1:1$ 1;
}
include /etc/nginx/careeratlas-cloudfront-realip.conf;
real_ip_header X-Forwarded-For;
real_ip_recursive on;
NGINX
printf '"%s" 1;\n' "$(cat /etc/careeratlas/origin-token)" > /etc/nginx/careeratlas-origin-token.conf
chmod 0600 /etc/nginx/careeratlas-origin-token.conf
# Use AWS's published address list; unknown future edge addresses fail closed.
curl -fsS https://ip-ranges.amazonaws.com/ip-ranges.json -o /etc/careeratlas/ip-ranges.json
python3 - <<'PY'
import json
from pathlib import Path
data = json.loads(Path('/etc/careeratlas/ip-ranges.json').read_text())
prefixes = sorted({p['ip_prefix'] for p in data['prefixes'] if p['service'] in ('CLOUDFRONT', 'CLOUDFRONT_ORIGIN_FACING')})
assert prefixes
Path('/etc/nginx/careeratlas-cloudfront-geo.conf').write_text(''.join(f'{p} 1;\n' for p in prefixes))
Path('/etc/nginx/careeratlas-cloudfront-realip.conf').write_text(''.join(f'set_real_ip_from {p};\n' for p in prefixes))
PY
cat > /etc/nginx/sites-available/careeratlas <<'NGINX'
server {
    listen 80 default_server;
    include /etc/nginx/careeratlas-tls.conf;
    server_name _;
    root /srv/careeratlas/current/public;
    index index.php index.html;
    server_tokens off;
    client_max_body_size 2m;
    access_log off;
    if ($origin_allowed = 0) { return 403; }
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy same-origin always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self'; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'" always;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ^~ /.well-known/acme-challenge/ { root /var/lib/letsencrypt; }
    location = /deployment.json { add_header Cache-Control "no-store"; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTPS on;
        fastcgi_param SERVER_PORT 443;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_hide_header X-Powered-By;
    }
    location ~ \.php$ { return 404; }
    location ~ /\. { deny all; }
}
NGINX
touch /etc/nginx/careeratlas-tls.conf
rm -f /etc/nginx/sites-enabled/default
ln -sfn /etc/nginx/sites-available/careeratlas /etc/nginx/sites-enabled/careeratlas
nginx -t
systemctl daemon-reload
systemctl enable nginx php8.3-fpm postgresql careeratlas-worker careeratlas-scheduler.timer careeratlas-backup.timer
systemctl restart php8.3-fpm nginx
systemctl start careeratlas-backup.timer fail2ban
echo 'Host bootstrap complete. Publish the first artifact to start application services.'
