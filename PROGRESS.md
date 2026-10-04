# QC Work Allocation System — Development Progress

## Overall Progress

PROJECT: QC Work Allocation System

START DATE: 2026-10-04
START TIME: 10:00

TOTAL MODULES: 30
COMPLETED MODULES: 29
IN PROGRESS: 1
REMAINING MODULES: 0

TOTAL PROGRESS: 97%

LAST UPDATED: 2026-10-04 10:33

---

## Module Status (original build)

| #  | Module                   | Status      | Progress | Notes                                                       |
| -- | ------------------------ | ----------- | -------: | ----------------------------------------------------------- |
| 1  | Project Inspection       | Completed   |     100% | Laravel 12.69.3 skeleton, DB schema inspected               |
| 2  | Foundation / Migrations  | Completed   |     100% | 11 migration files, migrate:fresh --seed verified           |
| 3  | Authentication           | Completed   |     100% | Login/logout/remember, rate limit, login logs verified      |
| 4  | Roles & Permissions      | Completed   |     100% | 7 roles, 29 permissions, middleware + Blade directives      |
| 5  | Employee Management      | Completed   |     100% | CRUD + skill mapping; /employees/create 500 fixed + retested|
| 6  | Department Management    | Completed   |     100% | Via config-driven master CRUD                               |
| 7  | QC Masters               | Completed   |     100% | 15 master modules; create/edit/delete verified over HTTP    |
| 8  | Work Request             | Completed   |     100% | Multi-test create verified + validation message             |
| 9  | Work Allocation          | Completed   |     100% | Smart candidates (skill+workload), history, verified E2E    |
| 10 | Analyst Workflow         | Completed   |     100% | accept/start/hold/submit verified via HTTP                  |
| 11 | Result Entry             | Completed   |     100% | Parameter-driven + simple result, instrument, verified      |
| 12 | Review/Rework            | Completed   |     100% | approve/rework/reject + reasons + history + resubmit        |
| 13 | Attachments              | Completed   |     100% | Upload/download/delete verified over HTTP (DB + disk)       |
| 14 | Notifications            | Completed   |     100% | In-app bell, center, mark read; seeded + runtime verified   |
| 15 | Dashboard                | Completed   |     100% | 3 role-specific dashboards, real KPIs + charts              |
| 16 | Reports                  | Completed   |     100% | 6 reports + filters; CSV export verified                    |
| 17 | Audit Logs               | Completed   |     100% | Logger service + admin viewer with filters                  |
| 18 | Security                 | Completed   |     100% | 403 checks verified for analyst/reviewer/guest              |
| 19 | Responsive UI + Testing  | In Progress |      90% | UI + full-route HTTP smoke (all GET routes, no 500s); QC-workflow PHPUnit tests still open |
| 20 | Documentation            | Completed   |     100% | README.md, DEPLOYMENT.md, HANDOVER.md written               |

## Module Status (enterprise-control upgrade)

| #  | Module                      | Status    | Progress | Notes                                                        |
| -- | --------------------------- | --------- | -------: | ------------------------------------------------------------ |
| 21 | User Registration           | Completed |     100% | /register, enable/disable, approval workflow, default role   |
| 22 | Session Duration / Inactivity | Completed |    100% | Login choice 30m–8h, server-side middleware, expiry message  |
| 23 | Concurrent User Limit       | Completed |     100% | DB setting, live-session counting, block + slot release      |
| 24 | Active Project/Workflow Limit | Completed |   100% | Guards WorkOrderService, real OPEN-order count               |
| 25 | Application License/Validity | Completed |    100% | DB dates, grace, 3 behaviours, 503 screen, server clock      |
| 26 | License Extension/Renewal   | Completed |     100% | By days or date, mandatory reason, full history + audit      |
| 27 | Admin Settings Integration | Completed |     100% | /settings centre, 5 tabs, same settings table, RBAC-guarded  |
| 28 | Security & Audit Integration | Completed |    100% | Server-side enforcement, CSRF/validation, audit on all four  |
| 29 | Tests & Regression          | Completed |     100% | 47 PHPUnit tests green + full HTTP regression pass           |
| 30 | Documentation Update        | Completed |     100% | README/DEPLOYMENT/HANDOVER/PROGRESS updated                 |

---

## Upgrade: what was added (nothing rebuilt)

- **Migrations (additive only):** `users.approved_at`, `users.status` += `pending`,
  `user_sessions.timeout_minutes`, new `license_histories` table, idempotent settings
  seed (15 keys; inserted only when missing). Ran with `php artisan migrate` — existing
  rows, users and QC data untouched (10 work orders, all history preserved).
- **New:** `AppSettings` (typed settings accessors), `SessionTracker` (session records +
  capacity), `EnforceLicense` + `CheckInactivity` middleware (web pipeline in that order),
  `RegisterController`, `SettingsController`, `UserSession`/`LicenseHistory` models,
  register + settings + active-users + licence-expired views, approve/reject actions.
- **Extended (not replaced):** login (timeout choice, pending/capacity checks, session
  tracking), `WorkOrderService` (project-limit guard), users UI (pending badge +
  approve/reject), sidebar (Administration section), layout (licence banner),
  management dashboard (4 real widgets + expiry warnings), `SESSION_LIFETIME=1440`.

## Verified Working — enterprise controls (HTTP + PHPUnit)

- Registration: page/POST when enabled; blocked page+POST with professional message
  when disabled; link hidden from login; pending user cannot sign in ("pending
  approval"); admin approve → active + approved_at + notification → login works;
  reject → inactive; analyst approve attempt → 403
- Session: login shows "Stay active for" with admin options (30m…8h, default 2h);
  chosen timeout stored in `user_sessions` (e.g. 30); beyond-max choice falls back to
  default; inactivity expiry redirects to login with "expired because of inactivity";
  activity refreshes the heartbeat; runtime clamp to admin max; logout marks session
  ended; remember-me does not bypass the policy
- Concurrent users: set max=1 → user without a slot gets exact "maximum number of
  active users…" message on the login page; existing sessions untouched; slot released
  after restore/logout/stale window; same-user re-login keeps distinct count; unlimited
  (0) allows everything; counting uses `user_sessions`, not the users table
- Active projects: set max=1 with 9 open → creation blocked with exact "maximum number
  of active projects…" message on the create form, 0 rows written; completing an order
  frees a slot; existing orders untouched; 0 = unlimited
- Licence: expired → analyst gets professional 503 "Application Validity Expired"
  (no internals), admin (system.settings) still 200 everywhere; grace → access + banner
  "License Expired — Grace Period Active"; extend by days → expiry 2027-01-02 +
  `license_histories` row + audit row, app active again; suspend/reactivate; readonly
  blocks POSTs but allows GETs; restrict_login blocks /login for guests; analyst
  settings routes → 403; client date/cookie headers cannot change the state
- Admin dashboard widgets show real values (Active Users 8/10, Active Projects 9/20,
  licence badge "No Expiry Set", Registration Enabled) — verified in browser render
- `/settings` (all 5 tabs) + `/settings-active-users` render for admins, 403 for others

## Verified Working — regression (existing system intact)

- Full lifecycle over HTTP after the upgrade: allocate → accept → start → result
  (99.5 PASS) → submit → under review → approve, statuses verified in DB at each step
- Attachments upload/download/delete, CSV export (text/csv + real rows), audit rows
  flowing for login/settings/license/attachments/status changes
- Full GET-route smoke across admin/analyst/reviewer: every page 200 (admin `/my-work`
  403 = by design); `/` redirects; guests sent to /login
- `php artisan test` → **47 passed (187 assertions), exit 0**

## Bugs Found & Fixed This Upgrade

- Session rows were matched by `session_id` only, which can rotate between requests —
  now a server-side session pointer (`_us_row`) tracks the row (touch/end use it)
- Duplicate LoginLog written on capacity-blocked login (removed)
- Baseline ExampleTest needed RefreshDatabase once middleware began reading settings
- Test-side: `alpha_dash` rejects dots in usernames; helper logged in with wrong
  password for self-registered users (tests corrected, app unchanged)

## Known Issues / Remaining

- QC-workflow PHPUnit tests (allocation ranking, review transitions) not written —
  those paths remain HTTP-verified (regression script pattern documented in HANDOVER)
- Forgot/reset password flow not implemented (change-password works)
- E-mail notifications not wired (in-app only); no roles/permissions management UI

## Testing Status

- Authentication + registration + approval: PASS (HTTP + PHPUnit)
- Session policy (choice, clamp, inactivity, remember-me, logout): PASS (PHPUnit)
- Concurrent user limit (block, release, stale, unlimited): PASS (HTTP + PHPUnit)
- Active project limit (block, free slot, unlimited): PASS (HTTP + PHPUnit)
- Licence (active/grace/expired/suspended, 3 behaviours, extension, history,
  authorization, server clock): PASS (HTTP + PHPUnit)
- Authorization on settings/license/approve routes: PASS (403s)
- Existing QC workflow + masters + attachments + export + dashboards: PASS (HTTP)
- Database migration on live data: PASS (additive, data preserved)
- `php artisan test`: PASS — 47/47, exit 0

## Known Limitations

- Forgot/reset password flows not implemented (change-password is implemented)
- Excel/PDF export replaced by CSV export (kept within time budget)
- Licence expiry exempts `system.settings` holders by design so renewals are always
  possible (documented in HANDOVER §1)
