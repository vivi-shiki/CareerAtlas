# Reproducible releases and continuous delivery

Status: implemented and verified through a successful automated release, browser/API smoke checks, post-switch failure recovery and an isolated database restore. Environment identifiers and detailed operational outcomes are recorded only in the ignored local runbook.

## Pipeline

The [workflow](../workflows/deploy.yml) verifies pull requests and pushes to `main`. Production publication runs only after verification succeeds on `main`. Manual dispatch from `main` can repeat a release. Deployments are serialized and an active deployment is not cancelled by a subsequent push.

Verification installs locked Composer/npm dependencies, runs PostgreSQL feature tests, formatting, frontend build/type checks, localization checks, documentation validation and release-script checks. Production packaging uses a fresh checkout with development dependencies excluded. Only an explicit application-file allowlist enters the archive: no `.env`, `.local`, Git history, tests, credentials or machine logs.

The `production` environment provides these settings:

| Setting | Kind | Meaning |
| --- | --- | --- |
| `DEPLOY_HOST` | Variable | Deployment host address |
| `DEPLOY_HOST_KEY` | Variable | Independently verified SSH known-hosts entry |
| `APP_URL` | Variable | Public HTTPS application origin |
| `DEPLOY_SSH_KEY` | Secret | Application-specific forced-command SSH credential |

Restrict the environment to the `main` branch. Pull requests never receive deployment credentials. Pin action dependencies to reviewed commits and update those pins deliberately. Rotate the deployment key by replacing both the host's authorized public key and the environment secret. Never disable SSH host-key verification or discover the key from the same unauthenticated connection used to deploy.

## Runtime and publication

The parameterized [host bootstrap](../../deploy/bootstrap.sh) prepares Ubuntu 24.04, PHP-FPM 8.3, Nginx, PostgreSQL, a queue worker, minute scheduler and daily backup timer. Credentials and concrete endpoint settings are supplied separately by the operator. Shared files and PostgreSQL data survive release replacement.

The forced SSH command accepts only `deploy` followed by a full commit hash and a bounded archive on standard input. Archive validation rejects traversal, absolute paths, links, devices, environment files and unexpected top-level paths. Extraction runs without root privileges; the installed release becomes root-owned. Application commands run as the web user. The deployment principal has no interactive shell over SSH and cannot forward ports.

Publication validates the artifact revision, compiles application configuration/views, enters maintenance mode, drains the worker, backs up PostgreSQL, applies additive migrations, runs `php artisan deploy:check`, atomically changes the current symlink, reloads PHP-FPM, and restarts the worker/scheduler. Local and public HTTP checks confirm both health and the expected commit. Brief maintenance downtime is expected on this single-host topology.

If publication fails after switching code, the release script attempts to restore the previous application symlink. It never reverses schema migrations or restores a backup automatically. Every migration must remain compatible with the preceding application release. Destructive schema changes require a separate reviewed maintenance procedure. If no previous release exists, investigate and repeat the initial deployment.

`deploy:check` rejects non-production environments, debugging, insecure cookies, nonpersistent sessions/cache, a non-PostgreSQL connection, missing tables and testing-only AI transport. AI can remain disabled with complete manual workflows. Enabling AI requires an explicitly authorized provider configuration and separate public/owner allowlists.

## HTTPS and cache boundary

The reference proxy configuration accepts only loopback health checks or requests from the configured delivery network with the configured origin header. It rejects direct public-origin access. It sets secure PHP request metadata only after that check, and resolves client addresses only through trusted proxy ranges. Proxy addresses must be refreshed when the provider changes them.

Use HTTPS for both browser and origin connections, pass through session cookies, CSRF and authorization headers, and disable delivery caching for all application routes, including errors. The public revision endpoint is not cached. The [origin certificate installer](../../deploy/origin-tls.sh) obtains a validated certificate for the public hostname through HTTP-01 and enables automatic renewal/reload. The delivery layer must forward that hostname and the challenge path, validate the origin certificate, and use HTTPS-only after initial certificate provisioning. An HTTP origin hop is not end-to-end TLS and is permitted only during initial empty-host certificate provisioning.

## Backup and recovery

The backup script creates PostgreSQL custom-format dumps before releases and daily, verifies that their archive catalogs can be read, and retains 14 days locally. Reading the archive catalog is not a restore test. Configure an independent host snapshot or off-host backup and record its retention/cost separately. A single host is a single failure domain.

Restore into a new, isolated database first. Verify schema and record counts, then exercise authentication and publication behavior before any planned database switch. Preserve the application encryption key with the private host backup. Never test a restore by overwriting the live database. Record measured recovery outcomes and limitations; do not claim point-in-time recovery from periodic dumps.

Operator-installed release/bootstrap scripts are a separate trusted control plane and are not silently replaced by application artifacts. Changes to those scripts require a host update. Review disk space and prune unused releases during maintenance, retaining the current and previous versions.

The web server must resolve both `index.php` and `index.html` as directory indexes. Reader documentation is static: `/docs/ja/` and the other language directories must serve their built `index.html` without authentication. Changing the bootstrap template alone does not update an existing host; back up the active configuration, validate it with `nginx -t`, and reload after applying this setting. The public release check runs `deploy/check_docs.py` against the documentation root and all three languages, covering explicit indexes, directory URLs with and without trailing slashes, and query strings.

Related: [Deployment contract](KB_CareerAtlas_Design_Deployment.md), [Building](../Guidelines/Building.md), [Testing](../Guidelines/Testing.md).
