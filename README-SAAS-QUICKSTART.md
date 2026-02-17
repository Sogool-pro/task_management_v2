# TaskFlow SaaS Quick Start

This is the fastest path to run TaskFlow as a multi-tenant SaaS on localhost.

If you want full explanations, read `README-SAAS.md`.

## 1. Prerequisites

1. PHP 8.1+
2. MySQL/MariaDB
3. Apache (XAMPP is fine)
4. SMTP credentials (for invite/signup/reset emails)

## 2. Configure environment

1. Copy `.env.example` to `.env.local`.
2. Set at least:
   - `APP_ENV=development`
   - `APP_URL=http://localhost/task_management_v2`
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

## 3. Import base database

Run:

```bash
mysql -u your_db_user -p -e "CREATE DATABASE IF NOT EXISTS your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u your_db_user -p your_db_name < task_management_db_mysql.sql
```

## 4. Run SaaS migrations

Run in phpMyAdmin SQL tab or CLI:

1. `sql_add_multi_tenancy_foundation.sql`
2. `sql_create_workspace_invites.sql`

Alternative for invites migration:

- `php run_migration_workspace_invites.php`

## 5. Create first workspace (owner account)

1. Open `http://localhost/task_management_v2/signup.php`
2. Enter:
   - Workspace Name (example: `Nehemiah`)
   - Full Name
   - Email
3. Login using the password sent by email.

## 6. Add employees (proper SaaS flow)

1. As workspace admin, open `invite-user.php`.
2. Send invite to employee email.
3. Employee opens invite link (`join-workspace.php?token=...`) and sets password.
4. Employee is automatically attached to the correct workspace.

Important:

- Invite sending and invite acceptance are blocked automatically if seat limit is reached.
- Invite sending and invite acceptance are also blocked if subscription/trial is not active.

## 7. Smoke test tenant isolation

Create two workspaces and verify:

1. Tasks do not cross workspaces.
2. Groups do not cross workspaces.
3. Messages/users do not cross workspaces.
4. Captures do not cross workspaces.
5. Dashboard ratings/stats are workspace-local.

## 8. Check billing/settings page

As workspace admin, open:

- `workspace-billing.php`

You should see plan status, seats used/left, and trial/period dates in one place.

If you need to manually upgrade plan capacity:

1. Go to `workspace-billing.php`
2. In `Manage Seats (Manual)`, set the new seat limit
3. Click `Update Seats`

Rule:

- Seat limit cannot be set below current active members.

## 9. Production safety flags

In production, keep:

1. `ALLOW_MAINTENANCE_SCRIPTS=0`
2. `ALLOW_GLOBAL_MAINTENANCE=0`
3. `APP_ENV=production`

## 10. If migration errors appear

If MariaDB reports `#1067 Invalid default value` during migration:

1. Fix the specific legacy column default in that table.
2. Rerun the failed migration statements.

For detailed troubleshooting, use `README-SAAS.md`.

For day-to-day support handling, use `README-SUPPORT-CHEATSHEET.md`.

## 11. New CSRF security behavior (important)

Sensitive SaaS forms/actions are now CSRF-protected:

1. Invite flow (`invite-user.php`, `join-workspace.php`, related `app/*` endpoints)
2. Billing seat update (`workspace-billing.php` -> `app/update-workspace-seat-limit.php`)
3. Task/group admin actions (create, review, delete)
4. Subtask/member actions (create, review, submit, resubmit, rate leader)
5. Profile/user management actions (profile update, user update, role update)
6. Auth/recovery forms (login, signup, forgot password, reset password)
7. Admin clock-out AJAX action (`user.php` -> `admin_clock_out.php`)
8. Chat AJAX actions (`messages.php` -> `app/ajax/*` send/read endpoints)
9. Attendance/capture AJAX actions (`index.php`/`capture.html` -> `time_in.php`, `time_out.php`, `save_screenshot.php`)
10. Notification read action (`app/notification.php` -> `app/notification-read.php`)

If you see `Invalid or expired request`:

1. Refresh the page
2. Submit again from the app UI (do not reuse old tab/form)
3. Log in again if session expired
