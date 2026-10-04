# QC Work Allocation System

A complete, enterprise-style **Quality Control work management platform** built with Laravel 12 —
from work request and multi-test scheduling, through smart analyst allocation, execution and
result entry, to review/rework, dashboards, reports and full audit trails.

Built as an internship project for a pharmaceutical / testing-laboratory QC department.

---

## Features

| Module | What it does |
| --- | --- |
| **Authentication** | Session login, login throttling (5 attempts), remember-me, inactive-account blocking, login audit trail |
| **RBAC** | 7 roles, 29 permissions, route middleware (`permission:...`), Blade directives (`@permission`, `@role`) |
| **QC Masters** | 15 config-driven master modules (departments, products, materials, sample types, priorities, skills, instruments, test types, methods, specifications, locations, settings, …) with search, validation and audit logging |
| **Work Requests** | Work orders carrying **multiple tests**, each with its own method, specification, priority and estimated duration; validation prevents empty requests |
| **Allocation** | Smart candidate ranking (skill match + current workload) per test, one-click allocate/reallocate, full allocation history |
| **Execution (Analyst)** | Accept → start → hold → resume → submit workflow, parameter-driven result entry with PASS/FAIL/OOS/OOT status, instrument selection, comments |
| **Review** | Approve / rework / reject with mandatory reasons, review history, automatic work-order status roll-up |
| **Attachments** | Per-work-order file upload (10 MB, whitelisted types), authorized download, delete, all audited |
| **Dashboards** | Three role-specific dashboards (Supervisor / Analyst / Management) with real KPIs and Chart.js charts |
| **Reports** | Overview, productivity, workload, overdue, rework, test-type load — with date filters and **CSV export** |
| **Notifications** | In-app bell + notification centre, role-targeted sends on allocate/submit/review events, mark-as-read |
| **Audit log** | Every create/update/delete/status change recorded with user, IP and old/new values |
| **User Registration** | Self-registration with admin toggle, optional approval workflow (pending → approve/reject), safe default role |
| **Session Policy** | Per-login "Stay active for" choice (30 min – 8 h), admin-defined options/default/maximum, server-side inactivity timeout |
| **Concurrent User Limit** | Admin-set maximum simultaneous users, real session-based counting, slot release on logout/expiry |
| **Active Project Limit** | Admin-set maximum active work requests; creation blocked politely at capacity, existing work untouched |
| **Application Validity** | DB-backed licence (activation/expiry/grace/behaviour), server-enforced expiry screen, warning thresholds, extension with full history |

---

## Enterprise controls (admin-configurable)

All four controls are database-backed (`settings` table), enforced **server-side**,
audited, and managed from the **Settings centre** (`/settings`, permission `system.settings`):

| Control | Where | What it does |
| --- | --- | --- |
| **Registration** | `/settings/users` | Toggle self-registration, require admin approval, choose the default new-user role. Pending users cannot sign in until approved under **User Management**. |
| **Session policy** | `/settings/session` | Allowed inactivity durations, default and maximum. Users pick "Stay active for" at login; middleware logs them out after that idle period with a clear message. Remember-me never bypasses the policy. |
| **Usage limits** | `/settings/usage` | Maximum simultaneous users (counted from live sessions, not the users table) and maximum active projects (work requests). `0` = unlimited. |
| **Application validity** | `/settings/license` | Activation/expiry dates, grace period, expiry behaviour (block / read-only / restrict login), warning thresholds, suspend/reactivate, extension by days or date with reason + history. |

Live widgets on the admin dashboard show real values: active users `7 / 10`,
active projects `4 / 5`, licence status with days remaining, registration state.
Active session details: `/settings-active-users`.

---

## Tech stack

- **Backend:** Laravel 12 (PHP 8.3), MariaDB/MySQL (XAMPP), session driver `database`
- **Frontend:** Blade, Bootstrap 5 + Bootstrap Icons (**vendored locally** — no CDN needed), vanilla JS, Chart.js
- **Assets:** no build step required for the demo (`public/css/app.css`, `public/js/app.js`, `public/vendor/…`)

---

## Requirements

| Component | Version |
| --- | --- |
| PHP | ≥ 8.2 (demo runs on 8.3) with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| MySQL / MariaDB | 5.7+ / 10.4+ (demo runs MariaDB 10.4 via XAMPP) |
| Composer | 2.x |
| Node.js | only needed if you want to rebuild Vite assets (optional) |

---

## Installation

```bash
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
```

Create the database, then configure `.env`:

```env
DB_DATABASE=qc_work_allocation
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed      # full schema + demo data
php artisan storage:link        # attachment downloads
php artisan serve               # http://127.0.0.1:8000
```

> Upgrading an existing install: run **`php artisan migrate`** (additive only —
> never drops tables or overwrites existing settings), see [DEPLOYMENT.md](DEPLOYMENT.md).

A ready-made helper also exists: `composer setup`.

Full environment notes (XAMPP, production checklist, troubleshooting): **[DEPLOYMENT.md](DEPLOYMENT.md)**
Architecture and maintenance guide: **[HANDOVER.md](HANDOVER.md)** · Status tracker: **[PROGRESS.md](PROGRESS.md)**

---

## Demo accounts

All passwords are `password`.

| Username | Role | See |
| --- | --- | --- |
| `admin` | super_admin | everything |
| `qc_manager` | qc_admin | masters, work orders, allocation, reports |
| `hod_qc` | qc_hod | approvals, reports, oversight |
| `supervisor_qc` | qc_supervisor | allocation, review queue |
| `analyst_raj` / `analyst_neha` / `analyst_amit` / `analyst_priya` | analyst | my-work queue, results |
| `reviewer_qc` | reviewer | review / rework / reject |
| `management` | management | dashboards + reports only |

---

## Workflow / status engine

Every work-order test moves through a validated state machine
(`app/Support/WorkStatus.php`, enforced by `App\Services\StatusService`):

```
NEW → ALLOCATED → ACCEPTED → IN_PROGRESS ⇄ ON_HOLD → SUBMITTED
    → UNDER_REVIEW → APPROVED → COMPLETED
                  ↘ REWORK → IN_PROGRESS (resubmit)
                  ↘ REJECTED → CANCELLED
```

- Illegal transitions are rejected server-side (e.g. submitting without a saved result,
  reviewing a test that is not under review, acting on another analyst's test → 403).
- Work-order status rolls up automatically from its tests' statuses.

---

## Project structure (short version)

```
app/Http/Controllers/   Auth, WorkOrder, Allocation, MyWork, Review, Master,
                        Employee, User, Report, Notification, AuditLog, …
app/Services/           StatusService, AllocationService, ReviewService,
                        WorkOrderService, NotificationService, AuditLogger
app/Support/            WorkStatus (status constants + transition map)
database/migrations/    11 migrations matching the QC schema
database/seeders/       Rbac, QcMaster, Organization, User, WorkOrder seeders
resources/views/        layouts + module folders + partials (badges, flash, empty)
public/vendor/          Bootstrap 5, Bootstrap Icons, Chart.js (local, no CDN)
routes/web.php          all routes with permission middleware
```

---

## Testing performed

The system was verified end-to-end over HTTP (curl with per-user sessions) covering:
login/logout for all roles, all three dashboards, master CRUD, work-order creation with
multi-test validation, allocation with live candidate JSON, the full status lifecycle,
result entry, approve/rework/reject with reasons, attachment upload/download/delete,
CSV export, notifications, and authorization checks (403s for wrong roles and guest
redirects).

The enterprise controls are covered by a PHPUnit suite (`php artisan test` — 47 tests):
registration settings & approval, inactivity timeout enforcement, concurrent-user
capacity, active-project limit, licence states (active/grace/expired/suspended),
expiry behaviours, extension + history, and authorization on every settings route.
See [PROGRESS.md](PROGRESS.md) for the module-by-module record.
