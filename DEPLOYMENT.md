# Deployment Guide — QC Work Allocation System

This guide covers local (XAMPP) deployment — the target environment for the internship
demo — plus a go-live checklist for a real server.

---

## 1. Local Deployment (Windows + XAMPP, the demo setup)

### 1.1 Prerequisites

| Component | Version used | Notes |
| --- | --- | --- |
| PHP (CLI) | 8.3.x | Must include `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| MySQL/MariaDB | MariaDB 10.4 (XAMPP) | Port 3306, default user `root`, no password |
| Composer | 2.x | Dependency install |
| Node.js | 24.x (optional) | Only if you rebuild Vite assets — **not required**, CSS/JS are vendored |

Check versions:

```bash
php -v
composer -V
mysql --version        # or C:\xampp\mysql\bin\mysql.exe --version
```

### 1.2 One-time setup

```bash
# 1. Dependencies
composer install

# 2. Environment
copy .env.example .env          # Git Bash: cp .env.example .env
php artisan key:generate

# 3. Database — create it first:
#    XAMPP Control Panel → MySQL → Start → Shell:
#      mysql -u root -e "CREATE DATABASE qc_work_allocation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Verify `.env`:

```env
APP_NAME="QC Work Allocation System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qc_work_allocation
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
```

```bash
# 4. Schema + demo data (drops existing tables — demo/reset only!)
php artisan migrate:fresh --seed

# 5. Public storage symlink (attachments)
php artisan storage:link
```

### 1.3 Run for the demo

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open <http://127.0.0.1:8000> and log in with any account from
[README.md](README.md#demo-accounts) (password: `password`).

Keep the XAMPP **MySQL** service running while presenting. The PHP dev server
console window must stay open (or run it as a background process).

### 1.4 Optional: run under Apache (XAMPP) instead of `artisan serve`

1. Copy/move the project to `C:\xampp\htdocs\qc-work-allocation`.
2. Point the vhost/DocumentRoot at the project's `public/` folder (**never** the project root).
3. Enable `mod_rewrite`; the bundled `public/.htaccess` handles routing.
4. Ensure `.env` has `APP_URL=http://localhost/qc-work-allocation` (matching the URL you use).
5. `storage/` and `bootstrap/cache/` must be writable by the Apache user.

---

## 2. Resetting the demo database

Between rehearsals it is useful to restore pristine data:

```bash
php artisan migrate:fresh --seed
```

This rebuilds all 11 migrations and re-seeds: 7 roles, 29 permissions, 10 users,
9 employees with skills, QC masters, 8 sample work orders across every status,
notifications and audit rows.

> **Warning:** `migrate:fresh` destroys all data. Run it only when you want a clean demo state.

---

## 3. Production / real-server checklist

| Item | Action |
| --- | --- |
| `.env` | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain` |
| App key | Generate once on the server; never reuse the dev key |
| Database | Create a dedicated MySQL user with privileges **only** on `qc_work_allocation`; set a strong password |
| Migrations | `php artisan migrate --force` |
| Web server | nginx/Apache with DocumentRoot = `public/`, HTTPS enforced |
| Permissions | `storage/` and `bootstrap/cache/` writable by the web user only |
| Sessions | `SESSION_DRIVER=database` (already default) or `redis` |
| Backups | Daily `mysqldump` of the database + `storage/app/public` uploads |
| Logs | Monitor `storage/logs/laravel.log`; ship to a log service if available |
| Updates | `composer install --no-dev --optimize-autoloader`, `php artisan config:cache`, `route:cache`, `view:cache` |

Queues are configured (`QUEUE_CONNECTION=database`) but the app currently performs all
notifications synchronously — no queue worker is required for demo or normal use.

---

## 4. Troubleshooting

| Symptom | Fix |
| --- | --- |
| `419 Page Expired` on a form | Session expired — reload the page and resubmit (fresh CSRF token) |
| `Connection refused` / blank page | Start MySQL in XAMPP; confirm `.env` DB settings |
| Attachments 404 | Run `php artisan storage:link` |
| Tables missing | `php artisan migrate:fresh --seed` |
| `No application encryption key` | `php artisan key:generate` |
| Login always fails | Check username (not email) and that the user's `status` is `active` |
| Stale routes/views after a change | `php artisan optimize:clear` |
| Port 8000 busy | `php artisan serve --port=8080` or stop the other process |

---

## 5. Verification smoke test (after any deploy)

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/login   # expect 200
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/        # expect 302 (redirect to login)
```

Then log in as `admin` / `password` and confirm: dashboard KPIs load, a work order
detail page renders, and `/reports` exports a CSV.
