# Melaz Motors — VPS Deployment Guide

This guide walks through deploying the Laravel + Filament backend to a fresh Linux VPS (Ubuntu 22.04 / 24.04 LTS recommended).

It assumes:
- Backend lives on a VPS reached over SSH.
- Domain DNS already points at the VPS.
- Frontend (the public website) is deployed separately and consumes this backend's API.

**Nothing in this guide will run automatically — every command is something an operator pastes into the VPS shell.**

---

## Table of contents

1. [Server prerequisites](#1-server-prerequisites)
2. [PHP version + extensions](#2-php-version--extensions)
3. [Database (MySQL/MariaDB)](#3-database-mysqlmariadb)
4. [Initial deployment](#4-initial-deployment)
5. [Environment variables (`.env`)](#5-environment-variables-env)
6. [`storage:link`](#6-storagelink)
7. [Migrations & seeding](#7-migrations--seeding)
8. [Creating the first admin user](#8-creating-the-first-admin-user)
9. [File permissions](#9-file-permissions)
10. [Web server (Nginx)](#10-web-server-nginx)
11. [HTTPS via Let's Encrypt](#11-https-via-lets-encrypt)
12. [Cache optimization](#12-cache-optimization)
13. [Clearing cache](#13-clearing-cache)
14. [Subsequent deploys (zero-downtime workflow)](#14-subsequent-deploys-zero-downtime-workflow)
15. [Queue & scheduler (optional)](#15-queue--scheduler-optional)
16. [Backups](#16-backups)
17. [Monitoring & logs](#17-monitoring--logs)
18. [Troubleshooting](#18-troubleshooting)
19. [Deployment checklist](#19-deployment-checklist)

---

## 1. Server prerequisites

| Component | Recommended |
|---|---|
| OS | Ubuntu 22.04 LTS or 24.04 LTS |
| RAM | 2 GB minimum, 4 GB+ if you expect real traffic |
| Disk | 20 GB+ (car image uploads grow over time — plan accordingly) |
| CPU | 2 vCPUs |
| Firewall | Open `22` (SSH), `80` (HTTP), `443` (HTTPS) only |
| Non-root user | A dedicated deploy user (`deploy`) with sudo. **Never deploy as root.** |

Install base utilities:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip software-properties-common ufw
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 2. PHP version + extensions

The project requires **PHP 8.1 or higher**. PHP 8.3 LTS is recommended for production.

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
  php8.3-fpm php8.3-cli \
  php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath \
  php8.3-gd php8.3-intl php8.3-fileinfo \
  php8.3-redis php8.3-opcache
```

Required extensions (verify with `php -m`):

| Extension | Why |
|---|---|
| `mysql` (PDO) | Database |
| `mbstring` | UTF-8 strings (Laravel core) |
| `xml`, `dom`, `tokenizer` | Laravel core |
| `curl` | Composer, S3 driver, outbound HTTP |
| `zip` | Composer package extraction |
| `bcmath` | Decimal math |
| `gd` (or `imagick`) | Filament image editor |
| `intl` | Number/currency formatting in Filament |
| `fileinfo` | File upload MIME detection |
| `openssl` | HTTPS, encryption |
| `redis` | If using `CACHE_DRIVER=redis` |
| `opcache` | Production performance |

Install Composer (latest):

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Recommended `php.ini` tweaks for production:

```ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
memory_limit = 256M
upload_max_filesize = 16M
post_max_size = 32M
max_file_uploads = 20

[opcache]
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0      ; CHANGE BACK TO 1 IF YOU EDIT FILES DIRECTLY ON THE SERVER
opcache.revalidate_freq = 0
```

After editing PHP config:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 3. Database (MySQL/MariaDB)

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Create a **dedicated database and user** for the app — do **not** use `root`:

```sql
CREATE DATABASE melaz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'melaz_user'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON melaz.* TO 'melaz_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Ensure MySQL only binds to `127.0.0.1` (default in Ubuntu). Never expose port `3306` publicly.

Recommended `my.cnf` tweaks for small VPS (≈ 2–4 GB RAM):

```ini
innodb_buffer_pool_size = 512M
innodb_file_per_table = 1
max_connections = 100
```

(Optional) Install Redis if you'll use it for cache/sessions/queue:

```bash
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
```

---

## 4. Initial deployment

As the `deploy` user, in `/var/www/`:

```bash
sudo mkdir -p /var/www/melaz_motors
sudo chown deploy:www-data /var/www/melaz_motors
cd /var/www/melaz_motors

git clone <YOUR_GIT_REMOTE> .

composer install --no-dev --optimize-autoloader --no-interaction

cp .env.example .env
php artisan key:generate
```

Then edit `.env` (see next section), then:

```bash
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan optimize        # config + route + view + event cache
```

---

## 5. Environment variables (`.env`)

The full template is `.env.example`. The values below are the ones that **must** be set for a production deployment. Never commit secrets.

### Application

```env
APP_NAME="Melaz Motors"
APP_ENV=production
APP_KEY=                       # generated by `php artisan key:generate`
APP_DEBUG=false                # CRITICAL — must be false in production
APP_URL=https://your-domain.com
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=melaz
DB_USERNAME=melaz_user
DB_PASSWORD=                   # the strong password you set above
```

### Cache / sessions / queue (Redis recommended for production)

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis         # set to "sync" if you don't need a worker
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true     # require HTTPS for the admin session cookie

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Filesystem

```env
FILESYSTEM_DISK=local          # not used by car images; cars uses its own disk
```

### Car images

```env
# Local public disk on the VPS (the default; needs storage:link).
CARS_IMAGES_DISK=public
CARS_IMAGES_DIRECTORY=cars
CARS_IMAGES_MAX_SIZE_KB=5120
CARS_IMAGES_RESIZE_MODE=contain
CARS_IMAGES_RESIZE_WIDTH=1600
CARS_IMAGES_RESIZE_HEIGHT=1067

CARS_DEFAULT_PER_PAGE=12
CARS_MAX_PER_PAGE=60
CARS_FILTER_CACHE_TTL=600
```

To switch to S3 / R2 / DigitalOcean Spaces later, configure that disk in `config/filesystems.php` and set `CARS_IMAGES_DISK=s3` (plus the standard `AWS_*` keys). No code changes required.

### CORS — the frontend domain

```env
CORS_ALLOWED_ORIGINS="https://your-frontend-domain.com,https://www.your-frontend-domain.com"
CORS_MAX_AGE=86400
```

**Leaving this empty in production = allow any origin = unsafe.** Fill it in.

### Admin seeder (used once, then either rotate or unset)

```env
ADMIN_NAME="Site Admin"
ADMIN_EMAIL="admin@your-domain.com"
ADMIN_PASSWORD="CHANGE_ME_STRONG_PASSWORD"
```

Rotate the password from the Filament UI on first login, then optionally clear these values so they don't sit in `.env` forever.

### Mail (optional — only if you wire admin password resets later)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Logging

```env
LOG_CHANNEL=daily              # rotates log file per day, keeps last 14
LOG_LEVEL=warning              # info/debug noise off in production
```

---

## 6. `storage:link`

Filament uploads car images into `storage/app/public/cars/{car_id}/...`. The web server, however, only serves files from `public/`. The bridge is a symlink.

```bash
php artisan storage:link
```

This creates `public/storage` → `storage/app/public`.

- **Run it once per server**, after the very first deploy.
- It's idempotent enough (`storage:link --force` if you ever need to recreate it).
- If you skip it, every uploaded image returns a 404 from the public site.

If you migrate to S3/R2 later, this symlink stops mattering (URLs come from the cloud provider directly).

---

## 7. Migrations & seeding

Schema changes:

```bash
# Initial deploy
php artisan migrate --force

# Every subsequent deploy with new migrations
php artisan migrate --force
```

`--force` skips the "are you sure you want to run this in production?" prompt — required for non-interactive deploy scripts.

Seeding (only when you need it):

```bash
# Default admin user, idempotent (won't duplicate)
php artisan db:seed --class=AdminUserSeeder --force

# Demo car catalog — DEV ONLY, never run on production.
# php artisan db:seed --class=CarSeeder
```

The cars seeder creates 30 demo cars with placeholder picsum.photos image URLs. Useful for staging or screenshots; **don't** run it on a real production database.

---

## 8. Creating the first admin user

Three equivalent options.

**Option A — Seeder driven by `.env` (recommended for repeatable deploys):**

```bash
# Set ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env first
php artisan db:seed --class=AdminUserSeeder --force
```

Idempotent — re-running updates the same admin in-place (keyed on email).

**Option B — Filament's interactive command, then promote:**

```bash
php artisan make:filament-user
# (prompts for name/email/password)

php artisan tinker
>>> \App\Models\User::where('email','you@example.com')->update(['is_admin' => true]);
```

**Option C — One-shot from Tinker:**

```bash
php artisan tinker
>>> \App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@your-domain.com',
...   'password' => 'CHANGE_ME_STRONG_PASSWORD',
...   'is_admin' => true,
... ]);
```

After logging in for the first time, change the password from inside Filament (account menu → top-right).

A user with `is_admin = false` (or no flag) cannot reach `/admin` — they'll get a 403, even with valid credentials. This is enforced by `User::canAccessPanel()`.

---

## 9. File permissions

The web server (`www-data` on Ubuntu) must be able to write to two directories:

- `storage/` — logs, cache files, uploaded images, sessions.
- `bootstrap/cache/` — Laravel's compiled service container, route cache, etc.

Everything else should be **read-only** to the web server.

```bash
cd /var/www/melaz_motors

# Ownership: deploy user owns files, www-data group owns the writable dirs
sudo chown -R deploy:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;

# Writable dirs (setgid so new files inherit www-data group):
sudo chmod -R 775 storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;

# Lock down the secrets file
sudo chmod 600 .env
sudo chown deploy:deploy .env
```

If you ever see "permission denied" in `storage/logs/laravel.log`, re-run the `775 storage bootstrap/cache` line.

---

## 10. Web server (Nginx)

```bash
sudo apt install -y nginx
```

Place this in `/etc/nginx/sites-available/melaz-motors`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;

    # Redirect all HTTP traffic to HTTPS (handled by certbot after step 11).
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/melaz_motors/public;
    index index.php;

    # Certificates filled in by certbot:
    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security: hide server tokens, disable directory listings.
    server_tokens off;
    autoindex off;

    # Compression for JSON / CSS / JS.
    gzip on;
    gzip_types application/json application/javascript text/css application/xml;
    gzip_min_length 256;

    # Upload size matches php.ini upload_max_filesize.
    client_max_body_size 32M;

    # Filament panel + API both come through Laravel.
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny dotfiles and sensitive paths.
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Cars images served directly by Nginx from the public storage symlink.
    location ~* ^/storage/.+\.(jpg|jpeg|png|webp|gif|avif)$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800, immutable";
        access_log off;
        try_files $uri =404;
    }

    # Static files in public/.
    location ~* \.(css|js|woff2?|svg|ico)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000, immutable";
        access_log off;
        try_files $uri =404;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/melaz-motors /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

> **Apache alternative**: the `public/` directory ships with a Laravel `.htaccess`. Point `DocumentRoot` at `public/`, enable `mod_rewrite`, set `AllowOverride All`, and disable directory listings (`Options -Indexes`). The rest of this guide applies unchanged.

---

## 11. HTTPS via Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot will:
- Issue a free Let's Encrypt certificate.
- Patch your Nginx vhost to use the new cert paths.
- Schedule an auto-renewal job (`systemctl status certbot.timer`).

Test renewal once:

```bash
sudo certbot renew --dry-run
```

The Laravel side is already production-ready for HTTPS — `AppServiceProvider` calls `URL::forceScheme('https')` when `APP_ENV=production`, and `TrustProxies::$proxies = '*'` so Cloudflare/Nginx forwarded headers are honored.

---

## 12. Cache optimization

Run these **after every deploy** that touches config, routes, views, or events:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Or all four at once via the convenience command:

```bash
php artisan optimize
```

What each one does:

| Command | What it caches | Where |
|---|---|---|
| `config:cache` | All `config/*.php` merged into one file | `bootstrap/cache/config.php` |
| `route:cache` | All registered routes (controller + parameters) | `bootstrap/cache/routes-v7.php` |
| `view:cache` | Compiles every Blade view ahead of time | `storage/framework/views/` |
| `event:cache` | Registered event listeners (only useful if you add events) | `bootstrap/cache/events.php` |

**Important**: when config is cached, `env()` calls outside of `config/*` files return `null`. Always read environment values via `config('foo.bar')`, not `env('FOO_BAR')`, in app code. This project already follows that convention.

If you add or change any code that uses `\Illuminate\Support\Facades\Event::listen()` directly (we currently don't), you'll want `event:cache` too. Otherwise it's a no-op safely.

---

## 13. Clearing cache

When a deploy fails, when config or routes seem stale, when permissions get reset — run:

```bash
php artisan optimize:clear
```

That single command clears all four caches above plus the application cache and compiled services. Then re-cache them with `php artisan optimize`.

Granular alternatives:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan cache:clear      # application data cache (Cache facade)
```

Filament also caches its own components (`filament:cache-components` / `filament:clear-cached-components`); the standard `optimize:clear` is enough in most cases.

---

## 14. Subsequent deploys (zero-downtime workflow)

A safe pattern for ongoing deploys (manual or via a script like Deployer/Envoyer):

```bash
cd /var/www/melaz_motors

# 1. Pull the new code
php artisan down --secret="some-long-random-string"     # optional maintenance mode
git pull --ff-only origin main

# 2. Refresh dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Run new migrations (always with --force in production)
php artisan migrate --force

# 4. Refresh caches
php artisan optimize:clear
php artisan optimize

# 5. Restart PHP-FPM so OPcache picks up the new files
sudo systemctl reload php8.3-fpm

# 6. Bring the site back up
php artisan up
```

If you set `opcache.validate_timestamps=0` (recommended for production performance), the `systemctl reload php8.3-fpm` step is **required** — without it, PHP will keep serving the old code from OPcache.

To skip maintenance mode entirely, use atomic-symlink deploys (Deployer, Envoyer, Forge) where the new release is built in a sibling directory and a `current` symlink is swapped at the end. Out of scope for this guide.

---

## 15. Queue & scheduler (optional)

This project does **not** require queue workers or scheduled tasks today. If you later add jobs (e.g. image post-processing) or scheduled tasks (e.g. cache warmers), set up:

**Queue worker** (`/etc/systemd/system/melaz-queue.service`):

```ini
[Unit]
Description=Melaz Motors queue worker

[Service]
User=deploy
Restart=always
ExecStart=/usr/bin/php /var/www/melaz_motors/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now melaz-queue
```

**Scheduler** (`crontab -e` as the `deploy` user):

```cron
* * * * * cd /var/www/melaz_motors && php artisan schedule:run >> /dev/null 2>&1
```

---

## 16. Backups

Two things to back up. Both are critical.

### 1. MySQL database

```bash
# Nightly via cron (deploy user, /etc/cron.d/melaz-backup)
0 2 * * * deploy mysqldump -u melaz_user -p"$MYSQL_PWD" melaz \
  | gzip > /var/backups/melaz-db-$(date +\%F).sql.gz
```

Then rotate (keep 30 days), and ship to an off-VPS location (S3, Backblaze B2, rsync to a second box).

### 2. Car images on disk

Image files live in `storage/app/public/cars/`. They are **not** in the database — losing this directory means every car listing has a broken thumbnail.

```bash
0 3 * * * deploy tar -czf /var/backups/melaz-images-$(date +\%F).tar.gz \
  -C /var/www/melaz_motors storage/app/public/cars
```

If you move images to S3/R2 later, rely on the cloud provider's versioning instead.

---

## 17. Monitoring & logs

Laravel logs go to `storage/logs/laravel-YYYY-MM-DD.log` (when `LOG_CHANNEL=daily`). Watch them on the server:

```bash
tail -f /var/www/melaz_motors/storage/logs/laravel-$(date +%F).log
```

Production logging recommendations:

- `LOG_CHANNEL=daily` rotates per day (default 14-day retention; raise via `config/logging.php`).
- Pipe to Sentry / Bugsnag / Datadog for alerting. Add the package and put the DSN in `.env` — no code change beyond that.
- Watch `/var/log/nginx/access.log` and `error.log` for HTTP-level issues.
- Watch the slow query log (`mysql.slow_query`) if traffic ramps up.

---

## 18. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| 500 with empty white page | `APP_KEY` missing, or permissions broken on `storage/` | `php artisan key:generate`, then the permissions block in §9 |
| 500 right after deploy | Stale cache | `php artisan optimize:clear` then `php artisan optimize` |
| Uploaded images return 404 | `php artisan storage:link` was never run | Run it once on the server |
| Admin login works but redirects back to login | Session cookie can't be set (HTTPS mismatch) | Verify `SESSION_SECURE_COOKIE` matches your scheme; verify `APP_URL` |
| 419 Page Expired in admin | Stale CSRF token; SESSION_DRIVER changed but old cookie still in browser | Clear browser cookies for the domain |
| `database password authentication failed` | `.env` doesn't match the MySQL user | Re-check `DB_USERNAME`/`DB_PASSWORD` and `grant` privileges |
| Filament shows unstyled page | Assets weren't published / cache stale | `php artisan filament:assets && php artisan view:clear` |
| `mysqldump` fails in cron | `$MYSQL_PWD` env var not exported in cron | Export it in `/etc/cron.d/melaz-backup` or use `~/.my.cnf` |
| 502 Bad Gateway | PHP-FPM down | `sudo systemctl status php8.3-fpm` |

---

## 19. Deployment checklist

Tick every box before announcing "we're live."

### Server
- [ ] Non-root `deploy` user with SSH key auth (password auth disabled).
- [ ] `ufw` firewall enabled — only `22`, `80`, `443` open.
- [ ] PHP 8.3 + all required extensions installed (`php -m` matches §2).
- [ ] Composer installed.
- [ ] MySQL installed; running; bound to `127.0.0.1`.
- [ ] Redis installed (if using `CACHE_DRIVER=redis`).
- [ ] Nginx installed and serving from `/var/www/melaz_motors/public`.

### Database
- [ ] Dedicated database (`melaz`) created.
- [ ] Dedicated user (`melaz_user`) with limited privileges, strong password.
- [ ] `mysql_secure_installation` done.

### Application
- [ ] Repo cloned to `/var/www/melaz_motors`.
- [ ] `composer install --no-dev --optimize-autoloader` succeeded.
- [ ] `.env` populated; `APP_KEY` generated.
- [ ] `APP_ENV=production` and **`APP_DEBUG=false`** (verify both).
- [ ] `APP_URL=https://your-domain.com` (no trailing slash).
- [ ] `CORS_ALLOWED_ORIGINS` set to the actual frontend domain(s) — **not** `*`.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`.
- [ ] `php artisan migrate --force` ran successfully.
- [ ] `php artisan db:seed --class=AdminUserSeeder --force` created the admin.
- [ ] `php artisan storage:link` ran.
- [ ] Permissions match §9 (`storage` and `bootstrap/cache` group-writable; `.env` is `600`).
- [ ] `php artisan optimize` ran (config/route/view/event cache built).

### Web server / TLS
- [ ] Nginx vhost in place; `nginx -t` passes.
- [ ] HTTPS certificate issued by certbot.
- [ ] HTTP-to-HTTPS redirect works (open `http://your-domain.com` — should 301 to https).
- [ ] `server_tokens off;` and `autoindex off;` in nginx config.
- [ ] gzip enabled for `application/json`.

### Smoke test
- [ ] `https://your-domain.com/api/cars` returns valid JSON.
- [ ] `https://your-domain.com/api/car-filters` returns valid JSON.
- [ ] `https://your-domain.com/api/cars/some-existing-slug` returns the car detail.
- [ ] Image URLs in the response are absolute `https://` URLs and load in a browser.
- [ ] `https://your-domain.com/admin` loads the Filament login page.
- [ ] Admin can log in and create a car with images; images appear via the public API.
- [ ] Force-deleting a car removes its image files from `storage/app/public/cars/{id}/`.

### Security headers (run `curl -I https://your-domain.com/api/cars`)
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `X-Frame-Options: SAMEORIGIN`
- [ ] `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`
- [ ] `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- [ ] `Access-Control-Allow-Origin: https://your-frontend.com` (NOT `*`)

### Operations
- [ ] Nightly MySQL backup configured.
- [ ] Nightly `storage/app/public/cars/` backup configured.
- [ ] Backups copied off-VPS (S3, Backblaze, rsync to second box).
- [ ] Admin password rotated from the seeded default.
- [ ] Optional: admin panel restricted to office/VPN IPs in Nginx.
- [ ] Optional: monitoring tool (UptimeRobot, BetterStack) hitting `/api/cars` every minute.
- [ ] Optional: error monitoring (Sentry / Bugsnag) installed.

---

You're done. The backend now serves the public website and Filament admin panel from one VPS, with images on disk, CORS locked to your frontend, HTTPS enforced, no public writes, and a clean upgrade path to S3/R2 whenever you outgrow local storage.
