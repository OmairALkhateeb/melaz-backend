# Melaz Motors — Hostinger VPS Quickstart

Concrete, copy-paste deployment for **a clean Ubuntu Hostinger VPS**, deploying via
**Git**, for the subdomain **`melaz-motors.livetech.it.com`**.

This is the short, domain-filled version. For the full reference (backups,
queues, monitoring, troubleshooting, security headers), see
[deployment-vps.md](deployment-vps.md).

> Every command below is something **you** paste into the VPS SSH shell. Nothing
> runs automatically.

---

## 0. Before you touch the server (on your laptop)

The project must be in a Git repo and pushed to GitHub/GitLab so the VPS can clone it.

```bash
# in c:\laragon\www\melaz_motors  (already git-initialised for you)
git remote add origin git@github.com:<you>/melaz_motors.git   # or https URL
git push -u origin main
```

`.env`, `.env.production`, `vendor/`, and `node_modules/` are **gitignored** — they
are NOT pushed. You'll create `.env` on the server from `.env.production` (step 5).

---

## 1. DNS — point the subdomain at the VPS

In your DNS provider for `livetech.it.com`, add an **A record**:

| Type | Name           | Value                |
|------|----------------|----------------------|
| A    | `melaz-motors` | `<YOUR_VPS_IP>`      |

Wait for it to resolve: `ping melaz-motors.livetech.it.com` should show the VPS IP.

---

## 2. Connect & create a deploy user (never deploy as root)

```bash
ssh root@<YOUR_VPS_IP>

adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy   # copy SSH key access
# then reconnect as deploy:
exit
ssh deploy@<YOUR_VPS_IP>
```

---

## 3. Install the stack (PHP 8.3, MySQL, Nginx, Composer)

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip software-properties-common ufw nginx mysql-server

sudo add-apt-repository ppa:ondrej/php -y && sudo apt update
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl \
  php8.3-fileinfo php8.3-opcache

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

Create the database and a dedicated user:

```bash
sudo mysql
```
```sql
CREATE DATABASE melaz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Use the DB_PASSWORD value from your local .env.production (it is pre-filled there).
CREATE USER 'melaz_user'@'127.0.0.1' IDENTIFIED BY 'PASTE_DB_PASSWORD_FROM_.env.production';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON melaz.* TO 'melaz_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Clone the project

```bash
sudo mkdir -p /var/www/melaz_motors
sudo chown deploy:www-data /var/www/melaz_motors
cd /var/www/melaz_motors

git clone git@github.com:<you>/melaz_motors.git .
composer install --no-dev --optimize-autoloader --no-interaction
```

---

## 5. Environment file

`.env.production` (filled in for this domain) is in the repo. Copy it to `.env`:

```bash
cp .env.production .env
php artisan key:generate          # fills APP_KEY
nano .env                         # review (DB/admin passwords are pre-filled)
```

`DB_PASSWORD` and `ADMIN_PASSWORD` come pre-filled with strong generated values;
`DB_PASSWORD` already matches the MySQL user created in §3 — no need to change
either to go live. Double-check: `APP_ENV=production`, `APP_DEBUG=false`,
`APP_URL=https://melaz-motors.livetech.it.com`.

> After your first admin login, rotate the password from the Filament UI
> (top-right account menu) so it no longer lives in `.env`.

---

## 6. Migrate, seed admin, link storage, optimize

```bash
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan optimize
```

> Do **not** run `CarSeeder` on production — it creates 30 demo cars.

---

## 7. Permissions

```bash
cd /var/www/melaz_motors
sudo chown -R deploy:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod g+s {} \;
sudo chmod 600 .env
chmod +x deploy.sh
```

---

## 8. Nginx vhost

```bash
sudo nano /etc/nginx/sites-available/melaz-motors
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name melaz-motors.livetech.it.com;

    root /var/www/melaz_motors/public;
    index index.php;

    server_tokens off;
    autoindex off;
    client_max_body_size 32M;

    gzip on;
    gzip_types application/json application/javascript text/css application/xml;
    gzip_min_length 256;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. { deny all; access_log off; log_not_found off; }

    location ~* ^/storage/.+\.(jpg|jpeg|png|webp|gif|avif)$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800, immutable";
        access_log off;
        try_files $uri =404;
    }

    location ~* \.(css|js|woff2?|svg|ico)$ {
        expires 30d;
        add_header Cache-Control "public, max-age=2592000, immutable";
        access_log off;
        try_files $uri =404;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/melaz-motors /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default     # remove Hostinger's default page
sudo nginx -t && sudo systemctl reload nginx
```

---

## 9. HTTPS (Let's Encrypt — free)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d melaz-motors.livetech.it.com
sudo certbot renew --dry-run
```

Certbot rewrites the vhost to add SSL and an HTTP→HTTPS redirect automatically.

---

## 10. Smoke test

```bash
curl -s https://melaz-motors.livetech.it.com/api/cars | head
curl -s https://melaz-motors.livetech.it.com/api/car-filters | head
```

Then in a browser:
- `https://melaz-motors.livetech.it.com/admin` → Filament login
- Log in with the admin email/password from `.env`, create a car with images,
  confirm the image URL in `/api/cars` loads over `https://`.

---

## 11. Future deploys

After the first setup, every update is just:

```bash
cd /var/www/melaz_motors && ./deploy.sh
```

(It pulls `main`, reinstalls deps, migrates, rebuilds caches, reloads PHP-FPM.
Edit `PHP_FPM_SERVICE` at the top of `deploy.sh` if you run a PHP version other
than 8.3.)

---

For backups, Redis, queue workers, monitoring, and the full security checklist,
continue in [deployment-vps.md](deployment-vps.md).
