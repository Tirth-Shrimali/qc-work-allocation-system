# Handover — QC Work Allocation System

Technical handover for maintainers. Read alongside [README.md](README.md)
(features, setup) and [DEPLOYMENT.md](DEPLOYMENT.md) (environment).

---

## 1. Architecture at a glance

```
Browser (Blade + Bootstrap 5, vendored assets)
   │  session cookie (database sessions) + CSRF token
   ▼
routes/web.php  ── auth middleware ── permission:<code> middleware ── EnsureUserIsActive
   ▼
Controllers (thin)  ──▶  Services (business rules)  ──▶  Eloquent Models  ──▶  MySQL
   ▼                              │
Blade views                      ├─ StatusService   (state machine + work-order roll-up)
(layouts/app + module folders)   ├─ AllocationService (candidate ranking, allocate/reallocate)
                                 ├─ ReviewService    (approve / rework / reject)
                                 ├─ WorkOrderService (multi-test creation, cancel)
                                 ├─ NotificationService (in-app notifications)
                                 └─ AuditLogger      (audit_logs rows: user, IP, old/new values)
```

### Request flow rules

1. **Authentication** — custom `Auth\LoginController` (username login, 5-attempt throttle
   with lockout, login audit in `login_logs`).
2. **Active check** — `EnsureUserIsActive` (appended to the `web` group) blocks
   deactivated users and enforces `access_start_date`/`access_end_date`.
3. **Authorization** — `permission` middleware alias (defined in `bootstrap/app.php`)
   checks `users → roles → permissions`. Super admin holds all 29 permissions.
   Controllers add ownership checks (e.g. analysts may only touch their own tests → 403).
4. **Workflow** — no controller mutates a status directly; everything goes through
   `StatusService::transitionTest()` which validates against `WorkStatus::TRANSITIONS`
   and then rolls up the parent work-order status.
5. **Side effects** — notifications + audit rows are written inside the services, so every
   state change is observable in the UI (bell) and in `/audit-logs`.

---

## 2. The status engine (source of truth)

`app/Support/WorkStatus.php`

- Constants: `NEW, ALLOCATED, ACCEPTED, IN_PROGRESS, ON_HOLD, SUBMITTED, UNDER_REVIEW,
  REWORK, APPROVED, REJECTED, COMPLETED, CANCELLED`
- `TRANSITIONS` — the **only** allowed edges. `canTransition()` is the single gate.
- `EXECUTION` — statuses where results may be entered (allocated/accepted/in progress/on hold/rework).
- `OPEN` — statuses that count as "in flight" for dashboards/overdue.

To change workflow rules, edit `TRANSITIONS` only; everything else follows.

**Roll-up:** `StatusService::syncWorkOrder($id)` derives the work-order status from its
tests (all completed → COMPLETED; any in progress → IN_PROGRESS; etc.).

### Enterprise-control pipeline (appended to the `web` group, in this order)

```
EnforceLicense → EnsureUserIsActive → CheckInactivity → … route middleware (auth, permission)
```

1. **`EnforceLicense`** — computes the licence state server-side from the `settings`
   table (`active / expiring / grace / expired / suspended / unconfigured`) using the
   application clock. Expired/suspended → professional `503` screen, modulated by
   `license.behavior` (`block` / `readonly` / `restrict_login`). Holders of
   `system.settings` always pass so an administrator can renew. Grace/expiry warnings
   are shared with the layout as a banner.
2. **`EnsureUserIsActive`** — (pre-existing) blocks deactivated/expired accounts.
3. **`CheckInactivity`** — reads the timeout chosen at login from the
   `user_sessions` row, clamps it to the admin maximum, and on idle overflow ends the
   session server-side (logout + invalidate + neutral “expired because of inactivity”
   message). Always heartbeats `last_activity_at`.

**`App\Support\AppSettings`** is the single accessor for every setting (typed getters,
safe defaults, no stale cache — admin edits take effect on the next request).
**`App\Services\SessionTracker`** owns the `user_sessions` table: start/touch/end of a
session, stale-slot cleanup, and the real active-user count used by the capacity limit.

---

## 3. Module map (route → controller → views)

| Area | Routes | Controller | Views |
| --- | --- | --- | --- |
| Auth / profile | `/login`, `/logout`, `/profile` | `Auth\LoginController`, `Auth\ProfileController` | `auth/login`, `profile` |
| Dashboards | `/dashboard` | `DashboardController` | `dashboards/{supervisor,analyst,management}` |
| Work orders | `/work-orders*` | `WorkOrderController` (+ `WorkOrderService`) | `work-orders/{index,create,show}` + `partials/test-row` |
| Allocation | `/allocation*` | `AllocationController` (+ `AllocationService`) | `allocation/{index,history}` + candidates modal |
| Analyst work | `/my-work*` | `MyWorkController` (+ `StatusService`) | `my-work/{index,show}` |
| Review | `/review*` | `ReviewController` (+ `ReviewService`) | `review/{index,show}` |
| Masters | `/masters/{master}*` | `MasterController` (config-driven) | `masters/{index,form}` |
| Employees | `/employees*` | `EmployeeController` | `employees/{index,form,show}` |
| Users | `/users*` | `UserController` | `users/{index,form}` |
| Reports | `/reports`, `/reports/export` | `ReportController` | `reports/index` + `reports/partials/*` |
| Notifications | `/notifications*` | `NotificationController` | `notifications/index` |
| Audit | `/audit-logs` | `AuditLogController` | `audit/index` |
| Attachments | `/work-orders/{id}/attachments`, `/attachments/{id}/*` | `AttachmentController` | (forms inside work-order / my-work views) |
| Comments | `/my-work/{id}/comments` | `WorkCommentController` | AJAX in `public/js/app.js` |
| Registration | `/register` (guest) | `Auth\RegisterController` | `auth/register` |
| Settings centre | `/settings*`, `/settings-active-users` | `SettingsController` | `settings/{index,active-users}` |
| User approval | `/users/{id}/approve\|reject` | `UserController` | buttons in `users/index` |
| Licence expiry screen | any route when expired | `EnforceLicense` middleware | `errors/license-expired` |

Shared UI: `resources/views/layouts/app.blade.php` (sidebar, topbar, notification bell),
partials `flash`, `empty`, `status-badge`, `priority-badge`.
Blade directives `@permission('code')` and `@role('name')` are registered in
`App\Providers\AppServiceProvider`.

---

## 4. How do I…?

### …add a new master module (e.g. "Vendors")

Add one key to `MasterController::configMap()`:

```php
'vendors' => [
    'label' => 'Vendors', 'singular' => 'Vendor',
    'model' => \App\Models\Vendor::class,
    'search' => ['vendor_code', 'name'],
    'columns' => [
        'vendor_code' => ['Code', 'text', null, true],       // first column = unique key
        'name'       => ['Name', 'text', null, true],
        'status'     => ['Status', 'status', null, true],     // 'active'/'inactive'
    ],
    'table' => ['vendor_code' => 'Code', 'name' => 'Vendor', 'status' => 'Status'],
],
```

Routes, validation rules, search, pagination, audit logging and the UI are all derived
from this config. Column types supported: `text`, `textarea`, `number`, `date`,
`select` (pass options array), `fk` (pass a closure returning the options), `status`.

### …add a permission

1. Insert a row in `permissions` (code + name) — best via `RbacSeeder` so fresh installs match.
2. Attach it to roles in `RbacSeeder` (or the Users screen at runtime).
3. Guard a route: `Route::get(...)->middleware('permission:my.code')`.
4. Hide UI: `@permission('my.code') … @endpermission`.

No cache to clear (permissions are loaded per request via relations).

### …add a report

Add a method in `ReportController` returning `['headers' => [...], 'rows' => [...]]`,
wire it into the `$data = match($report)` in both `index()` and `export()` (CSV export
comes for free), then add a tab in `resources/views/reports/index.blade.php`.

### …change a workflow transition

Edit `WorkStatus::TRANSITIONS`. Add a `my-work` action mapping in
`MyWorkController::action()` if analysts need to trigger it. Tests are your safety net —
re-run the lifecycle sequence (README §Workflow).

### …change a session/registration/limit/licence policy

All values live in the `settings` table; change them at **`/settings`** — never in code.
Validation keeps them consistent (e.g. the default duration must be one of the allowed
options and ≤ the maximum; expiry ≥ activation). If a new policy key is ever needed:
add an idempotent row in migration `2026_10_04_000003`, an accessor in `AppSettings`,
and a form field in `resources/views/settings/index.blade.php`.

### …renew / extend the application licence (company process)

1. Sign in as an administrator (`system.settings`) — works even when the licence is
   expired; the expiry gate never blocks this role.
2. Open `/settings/license` → *Extend / Renew Validity*: choose **+days** or a
   **new expiry date**, enter a mandatory reason, submit.
3. The licence becomes active again immediately; `license_histories` records previous
   and new expiry, actor and reason; the audit trail records the event; the warning
   marker resets so future thresholds fire again.

Suspend/reactivate lives on the same page (also history + audit logged).

### …add an e-mail / external notification

`NotificationService::toRoles()` / `send()` currently create DB rows (in-app bell).
Add the transport (e.g. `Mail::to(...)->send()`) beside the existing insert; queue is
already configured (`QUEUE_CONNECTION=database`) if you want it async.

---

## 5. Database schema overview

| Group | Tables |
| --- | --- |
| RBAC | `roles`, `permissions`, `role_permissions`, `user_roles`, `users`, `login_logs`, `user_sessions` |
| Organization | `employees`, `departments`, `designations`, `employee_skills`, `skills` |
| QC masters | `products`, `materials`, `sample_types`, `work_categories`, `priorities`, `instrument_types`, `instruments`, `test_types`, `test_methods`, `test_parameters`, `specifications`, `locations`, `settings`, `statuses` |
| Work | `work_orders`, `work_order_tests`, `work_allocations`, `allocation_histories` |
| Results & review | `test_results`, `test_result_parameters`, `review_histories`, `work_comments`, `work_attachments` |
| System | `notifications`, `audit_logs`, `settings`, `license_histories`, `user_sessions`, `migrations`, `sessions`, `cache` |

The upgrade reuses the existing key/value **`settings`** table (groups: `general`,
`registration`, `session`, `usage`, `license`), the existing **`user_sessions`** table
(login time, last activity, logout reason + new `timeout_minutes`), and the existing
`users.status`/audit/notification systems — no parallel structures were introduced.

Migrations are timestamped `2026_01_01_0000xx_*` (11 files) and **match an existing
schema exactly** — do not regenerate them blindly; edit in place if the schema must change,
then `migrate:fresh --seed`.

**Seeders** (`database/seeders/`): `RbacSeeder` → `QcMasterSeeder` → `OrganizationSeeder`
→ `UserSeeder` → `WorkOrderSeeder`, run by `DatabaseSeeder`. `WorkOrderSeeder` guards on
`WorkOrder::count() > 0`, so it will not duplicate data into a used database.

---

## 6. Testing approach

A baseline PHPUnit suite exists and is green (`php artisan test` — guest-redirect,
login-page, unit smoke). Domain-level feature tests are the main gap (see §7).
Everything else has been verified **over HTTP** with per-user cookie jars:

```bash
# pattern: login → scrape CSRF → POST → assert redirect + DB row
curl -s -c jar.txt http://127.0.0.1:8000/login -o page.html
TOKEN=$(grep -o 'name="_token" value="[^"]*"' page.html | head -1 | sed 's/.*value="//;s/"//')
curl -s -b jar.txt -c jar.txt -X POST http://127.0.0.1:8000/login \
     -d "_token=$TOKEN" -d "username=admin" -d "password=password"
```

**Enterprise-control suite (47 tests, all green):** `tests/Feature/`
`RegistrationTest` (enable/disable, pending approval, duplicates, password rules,
role/status tampering, unauthorized approve → 403), `SessionPolicyTest` (choice
stored, fallback above max, inactivity expiry + message, activity refresh, runtime
max clamp, logout, remember-me), `ConcurrentUsersTest` (blocked at capacity with the
exact message, slot release, same-user re-login, stale cleanup, unlimited, session-
based counting), `ProjectLimitTest` (blocked at capacity, slot frees on completion,
unlimited, existing orders untouched), `LicenseTest` (active/grace/expired/suspended,
all three behaviours, privileged access, extension by days/date + history, invalid
shortening, authorization, server-clock enforcement).

Verified end-to-end (see PROGRESS.md): auth for all roles, master CRUD, work-order
create + validation, allocation + candidates JSON, full status lifecycle, result entry,
approve/rework/reject, attachments upload/download/delete, CSV export, notifications,
audit rows, and 403/redirect authorization checks.

> Windows curl note: pass upload files with a **relative** path (`-F "file=@./x.txt"`);
> `@/tmp/...` is not resolvable by native curl.

---

## 7. Known limitations

- **PHPUnit domain tests**: the enterprise-control suite (registration, session,
  limits, licence — 47 tests) is green; QC-workflow features (allocation ranking,
  review transitions) are still verified over HTTP rather than in PHPUnit.
- **E-mail notifications**: in-app only; no mail transport wired.
- **`roles` CRUD**: roles/permissions are seeded; there is no UI to edit them
  (`roles.manage` permission exists for a future screen).
- **Work-order line editing**: tests can be added at creation; after that only
  status/cancel — no editing an existing order's test list.
- **`welcome.blade.php`** is the unused Laravel default (`/` redirects to login; harmless).
- Locale is English-only; no multi-language support.

---

## 8. Suggested next steps (priority order)

1. PHPUnit feature tests for `WorkStatus`/`StatusService` + a login/permission smoke test.
2. Password change enforcement + profile photo upload polish (upload field exists).
3. Gantt/Calendar view of allocations on `/allocation/history`.
4. Role/permission management UI for `super_admin`.
5. Export attachments as a ZIP for a work order.
6. Activity dashboard widget: "recent audit entries" for admin.

---

## 9. Where things live — file checklist

```
app/Support/WorkStatus.php          status machine — edit transitions here
app/Support/AppSettings.php         ALL enterprise settings accessors (typed, safe defaults)
app/Services/StatusService.php      transition + roll-up enforcement
app/Services/SessionTracker.php     user_sessions tracking, active-user count, slot cleanup
app/Services/AllocationService.php  candidate scoring (skill match + workload)
app/Http/Controllers/…              17 controllers, all thin
app/Http/Middleware/EnforceLicense.php    server-side licence gate + expiry screen
app/Http/Middleware/CheckInactivity.php   server-side inactivity timeout
bootstrap/app.php                    middleware aliases + web-group pipeline order
app/Providers/AppServiceProvider.php @permission / @role Blade directives
database/migrations/2026_10_04_*     additive upgrade migrations (3)
database/seeders/                   demo data (roles, users, masters, work orders)
resources/views/settings/           settings centre (tabs: users/session/usage/license)
resources/views/auth/register.blade.php  self-registration form
resources/views/errors/license-expired.blade.php  professional 503 screen
resources/views/layouts/app.blade.php  app shell (sidebar, topbar, licence banner)
public/css/app.css                  design system (CSS variables, cards, badges)
public/js/app.js                    toasts, confirm dialogs, AJAX comments, sidebar
tests/Feature/*Test.php             47 enterprise-control tests (php artisan test)
routes/web.php                      67 routes, permission middleware
storage/logs/laravel.log            first place to look when a page 500s
```
