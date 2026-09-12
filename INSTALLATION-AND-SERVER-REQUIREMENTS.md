# How to Install the App (from zero)

Complete server preparation and installation guide for the Complaint Desk web application.
Covers extension lists, system packages, PHP/Node versions, database setup, and every command needed to go from a blank server to a working install.

---

## 1. Version requirements (read first)

| Piece | Requirement | Notes |
|---|---|---|
| **PHP** | **8.2, 8.3 or 8.4** | `composer.json` requires `^8.2`. **Do NOT use PHP 8.5** — `phpoffice/phpspreadsheet` 1.30.6 caps at `<8.5`. PHP 8.4 recommended. |
| **Composer** | 2.x (current) | PHP package manager. |
| **Node.js** | **20.19+ or 22.12+** (Node 22 LTS recommended) | Vite 7 / Tailwind 4 requirement. Only needed at build time. |
| **Database** | **Microsoft SQL Server** (2019/2022 +) | The app runs on `DB_CONNECTION=sqlsrv`. |
| **Web server** | **Apache** with `mod_rewrite`, docroot → app `public/` | XAMPP on Windows; apache2/nginx on Linux. |
| **System packages** | **Microsoft ODBC Driver 17 or 18** | Required by PHP to talk to SQL Server. |
| **System binaries** | None | PDFs = dompdf (pure PHP); images = PHP GD; no wkhtmltopdf/ImageMagick CLI needed. |
| **Cron / queue workers** | None | The app has no scheduled tasks and dispatches no queue jobs. |

---

## 2. PHP extensions to enable

### 2.1 Mandatory extensions

Enable all of these in `php.ini`:

```
extension=ctype
extension=dom
extension=fileinfo
extension=filter
extension=gd
extension=hash
extension=iconv
extension=json
extension=libxml
extension=mbstring
extension=openssl
extension=pdo
extension=session
extension=simplexml
extension=tokenizer
extension=xml
extension=xmlreader
extension=xmlwriter
extension=zip
extension=zlib
```

- **`gd` especially matters** — evidence photos are compressed with `imagecreatefromstring()/imagejpeg()` in `app/Services/Visitors/VisitorPhotoService.php`. Without GD you get:
  `Call to undefined function App\Services\Visitors\imagecreatefromstring()` on photo upload.
- **`calendar`** — required by `khaled.alshamaa/ar-php` (Arabic dates / PDF glyph shaping).
- **`mbstring`** — required for Arabic handling and Excel/PDF.

### 2.2 SQL Server driver extensions (production)

```
extension=sqlsrv
extension=pdo_sqlsrv
```

Plus the **Microsoft ODBC Driver for SQL Server** (v17 or v18) installed at OS level —
these PHP extensions will not load without it.

### 2.3 Recommended / optional

| Extension | Why |
|---|---|
| `curl` | Guzzle HTTP handler (best practice) |
| `intl` | Excel/validation internationalization |
| `bcmath`, `gmp` | Performance in some transitive libraries |
| `pcntl`, `posix` | Only needed if you ever run `php artisan queue:work` on Linux |
| `redis` (`ext-redis`) | Only if you switch cache/queue/session to Redis (default is `database`) |

---

## 3. Install by operating system

### 3.1 Linux (Ubuntu / Debian)

```bash
# System packages + PHP + extensions + Apache
sudo apt update
sudo apt install -y apache2 libapache2-mod-php \
  php8.4 php8.4-cli php8.4-fpm php8.4-curl php8.4-mbstring php8.4-xml \
  php8.4-zip php8.4-gd php8.4-intl php8.4-bcmath php8.4-sqlite3 \
  unzip git curl nginx 2>/dev/null

# Microsoft ODBC Driver 18 + SQL Server PHP driver
curl https://packages.microsoft.com/keys/microsoft.asc | sudo tee /etc/apt/trusted.gpg.d/microsoft.asc
sudo sh -c 'echo "deb [arch=amd64] https://packages.microsoft.com/ubuntu/22.04/prod noble main" > /etc/apt/sources.list.d/mssql-release.list'
sudo apt update
sudo ACCEPT_EULA=Y apt install -y msodbcsql18
sudo ACCEPT_EULA=Y apt install -y php8.4-sqlsrv php8.4-pdo-sqlsrv unixodbc-dev

php -m | grep -iE 'gd|sqlsrv|pdo_sqlsrv|mbstring|xml|zip|curl'
```

> Package names (e.g. `php8.4-*`) depend on the PHP version you installed. Match them to your `php -v`.

### 3.2 Linux (CentOS / RHEL)

```bash
sudo yum install -y epel-release
sudo yum install -y httpd php php-cli php-common php-mbstring php-xml php-zip php-gd php-curl php-intl php-bcmath php-pdo unzip git

# Microsoft repo + ODBC + SQL Server PHP drivers
sudo curl -o /etc/yum.repos.d/mssql-release.repo https://packages.microsoft.com/config/rhel/9/prod.repo
sudo ACCEPT_EULA=Y yum install -y msodbcsql18 php-sqlsrv php-pdo-sqlsrv unixODBC-devel

php -m | grep -iE 'gd|sqlsrv|pdo_sqlsrv|mbstring|xml|zip'
```

### 3.3 Windows / XAMPP

1. Install **XAMPP** (includes Apache + PHP).
2. Download the SQL Server PHP drivers for your PHP version from
   https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
   — get `php_sqlsrv.dll` + `php_pdo_sqlsrv.dll` and drop them in `C:\xampp\php\ext\`.
3. Install the **Microsoft ODBC Driver 18 for SQL Server**.
4. Edit `C:\xampp\php\php.ini` and uncomment/add:

   ```ini
   extension=gd
   extension=curl
   extension=intl
   extension=zip
   extension=sqlsrv
   extension=pdo_sqlsrv
   ```
5. Restart Apache through the XAMPP Control Panel.

Verify:
```powershell
php -m | Select-String -Pattern 'gd|sqlsrv|pdo_sqlsrv|mbstring|xml|zip'
```

---

## 4. Install Composer and Node

### Linux
```bash
# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Node 22 LTS
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v
```

### Windows / XAMPP
- Composer: download the **Composer-Setup.exe** installer from https://getcomposer.org/download/ .
- Node.js: download the **LTS** installer from https://nodejs.org/en/download and install.

> On Windows PowerShell, use **`npm.cmd run build`**, not `npm run build` (PowerShell blocks `npm.ps1` under its execution policy).

---

## 5. Create the database

```sql
-- Run on SQL Server (as an admin) — one-time
CREATE DATABASE complaints;
```

The app also needs these tables — they are created by migrations (step 7), **not** manually:
`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`,
plus all business tables (`customers`, `complaints`, `branches`, `visitors_*`, …).
No extra database users are required if the app account has rights on `complaints`.

---

## 6. Deploy the application files

```bash
# Example: copy the project to /var/www/complaint (Linux)
sudo mkdir -p /var/www/complaint
sudo cp -R . /var/www/complaint
sudo chown -R www-data:www-data /var/www/complaint

# Server docroot must point to the app's public/ folder:
# Apache:  DocumentRoot /var/www/complaint/public
# nginx:   root /var/www/complaint/public; location / { try_files $uri $uri/ /index.php?$query_string; }
```

---

## 7. Install and configure the app

```bash
cd /path/to/app

# 1) PHP dependencies (uses composer.lock — pinned versions)
composer install --no-dev --optimize-autoloader

# 2) Environment file
cp .env.example .env

# 3) Edit .env — use a PHP editor / nano; then fill in production values:
#    DB_CONNECTION=sqlsrv
#    DB_HOST=127.0.0.1   (or your SQL Server host)
#    DB_PORT=1433
#    DB_DATABASE=complaints
#    DB_USERNAME=<sql user>
#    DB_PASSWORD=<sql password>
#    DB_ENCRYPT=no
#    DB_TRUST_SERVER_CERTIFICATE=true
#    APP_URL=http://your-domain-or-ip/complaint/public   (must match how you reach the site)
#    SESSION_COOKIE=complaint_session

# 4) Encrypt app key
php artisan key:generate

# 5) Run database migrations (creates all tables; safe, adds only)
php artisan migrate --force

# 6) Seed baseline data
#    FRESH INSTALL:  php artisan db:seed
#    PRODUCTION / EXISTING DATABASE: DO NOT run the full seeder blindly —
#    PermissionSeeder uses syncPermissions() and would overwrite real role
#    rights. See section 8b below.

# 7) File permissions for the web user
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage/framework storage/logs storage/fonts bootstrap/cache
mkdir -p storage/fonts && chmod 775 storage/fonts   # dompdf font cache

# 8) Frontend build
npm install
npm run build            # production build -> public/build/ (needed for CSS/JS)

# 9) Clear & rebuild caches
php artisan config:clear
php artisan view:clear
php artisan view:cache
php artisan route:cache   # optional, after all routes are final
php artisan permission:cache-reset
```

---

## 8. Seeders — two situations

### 8a. Fresh install (empty database) — safe
```bash
php artisan db:seed
```
This creates: Super Admin user, roles + permissions, master data (branches, services,
sources, categories, types, priorities, statuses), demo customers/complaints, and the
visitors master data.

### 8b. Production / existing database — BE CAREFUL
- **Never** run `php artisan db:seed` blindly on a database that already has real data.
- Permissions are created by `PermissionSeeder` which **overwrites** role→permission
  assignments (`syncPermissions`). If you need to add a single new permission to production:

```bash
php artisan tinker --execute="\Spatie\Permission\Models\Permission::findOrCreate('dashboard.visitors.view', 'web'); echo 'ok';"
php artisan permission:cache-reset
```
  then assign it to the role via the **Roles** page in the UI.

---

## 9. Web server configuration notes

### Apache
- `mod_rewrite` must be enabled (Laravel's `public/.htaccess`).
- Docroot → app `public/`.
### nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
}
```
### HTTPS
Required in production for PWA "install app" features (browsers allow service
workers only on HTTPS, except `localhost`). Put the app behind a TLS certificate
(Let's Encrypt / reverse proxy).

---

## 10. Verify the install

```bash
# Extensions ready
php -m | grep -iE 'gd|sqlsrv|pdo_sqlsrv|mbstring|dom|xml|zip|curl'

# Version checks
php -v                      # 8.2 - 8.4
node -v && npm -v           # Node 22 LTS

# Laravel ready
composer install --no-interaction --prefer-dist --no-dev
php artisan --version
php artisan migrate:status  # all rows "Ran"
php artisan route:list

# Runtime smoke test
php artisan tinker --execute="echo App\Models\Branch::count();"
```

Open in the browser:
```
http://YOUR-HOST/complaint/public/login
```

If `php artisan test` is desired on a fresh install:
```bash
composer install        # includes dev deps
php artisan test
```

---

## 11. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `Call to undefined function ... imagecreatefromstring()` | **GD extension not loaded.** Enable `extension=gd`, restart Apache. |
| `could not find driver` / PDOException on connect | `pdo_sqlsrv`/`sqlsrv` missing, or **Microsoft ODBC Driver** not installed. |
| Login redirects to wrong host | `.env` `APP_URL` mismatch. It should match the public URL, e.g. `http://41.129.145.38:8080/complaint/public`, then `php artisan config:clear`. |
| Syndicated page shows old JS/CSS after deploy | Run `npm run build` (new hashed assets) + `php artisan view:clear`. |
| `This action is unauthorized.` for a real user | Role/user lacks the required **permission** (e.g. `dashboard.visitors.view`). Add it in Roles; run `php artisan permission:cache-reset`. |
| Excel/PDF fail | Enable `zip`, `zlib`, `dom`, `xml*`, `gd` extensions. |
| Arabic PDF renders wrong | `mbstring` + `calendar` + a proper font for `storage/fonts` (dompdf). |
| 419 Page Expired | Session/Cookie config; run migrations (sessions table), set `SESSION_DRIVER=database`. |
| Blank page after `.env` edit | `php artisan config:clear` / `config:cache`. |

---

## 12. Environment reference

### `.env` — production baseline
```
APP_NAME="Complaint Desk"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain-or-ip/complaint/public
APP_LOCALE=en

DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=complaints
DB_USERNAME=sa
DB_PASSWORD=********
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_COOKIE=complaint_session
FILESYSTEM_DISK=local
MAIL_MAILER=log
```