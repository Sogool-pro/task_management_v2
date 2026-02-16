# TaskFlow SaaS Guide (Multi-Tenant Edition)

This guide explains what has already been changed in your project to support SaaS, how to set it up safely, and how the workspace (tenant) flow works in plain language.

It is written for beginners.

## 1. What "SaaS" means in this project

In this app, SaaS means:

1. Many companies can use the same app.
2. Each company has its own workspace (called a tenant/organization).
3. Users should only see data inside their own workspace.
4. Workspace admins can invite employees to join their workspace.

## 2. Core concepts (simple definitions)

- `Organization` or `Workspace`: One company/account in your SaaS.
- `Tenant`: Same meaning as workspace/organization.
- `Owner`: The first admin who creates the workspace.
- `Admin`: Can manage users/tasks/groups in that workspace.
- `Employee`: Works inside the workspace but has limited permissions.
- `Super Admin`: System-level admin account used for maintenance and global control, not normal tenant operations.

## 3. What has been implemented so far

### 3.1 Multi-tenant database foundation

Implemented via:

- `sql_add_multi_tenancy_foundation.sql`
- `inc/tenant.php`

Key changes:

1. Added `organizations` table.
2. Added `organization_members` table.
3. Added `subscriptions` table.
4. Added `organization_id` columns to tenant-owned tables like:
   - `users`
   - `tasks`
   - `task_assignees`
   - `groups`
   - `group_members`
   - `subtasks`
   - `notifications`
   - `attendance`
   - `screenshots`
   - `chats`
   - `group_messages`
   - `group_message_reads`
   - `leader_feedback`
   - `password_resets`
5. Backfilled existing data into a default workspace.
6. Added tenant indexes and foreign keys.

### 3.2 Tenant-scoping helper layer

Implemented in:

- `inc/tenant.php`

Main helpers:

1. `tenant_get_current_org_id()`
2. `tenant_get_scope(...)`
3. `tenant_resolve_user_org(...)`
4. `tenant_resolve_user_membership_role(...)`

Purpose:

- Automatically append `organization_id = ?` scope to SQL queries when tenant mode is enabled.

### 3.3 Auth/workspace-aware login and signup

Implemented in:

- `signup.php`
- `app/signup.php`
- `login.php`
- `app/login.php`

Behavior now:

1. Signup creates a workspace + owner/admin account.
2. Login resolves and stores workspace context in session:
   - `$_SESSION['organization_id']`
   - `$_SESSION['organization_name']`
   - `$_SESSION['organization_role']`
3. Suspended/canceled workspaces are blocked at login.

### 3.4 Proper employee join flow (invite-based)

Implemented in:

- `invite-user.php`
- `app/invite-user.php`
- `app/cancel-invite.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `sql_create_workspace_invites.sql`
- `run_migration_workspace_invites.php`
- `app/send_email.php` (workspace invite email sender)

Behavior now:

1. Admin invites employee by email.
2. Employee receives tokenized invite link.
3. Employee opens `join-workspace.php?token=...`.
4. Employee sets password and is created inside the correct workspace.
5. Invite is marked accepted.

### 3.5 Direct add-user flow deprecated

Changed in:

- `add-user.php`
- `app/add-user.php`

Behavior:

- Admins are redirected to invite flow instead of direct credential creation.

### 3.6 Maintenance/debug scripts hardened

Implemented in:

- `maintenance_guard.php`
- `maintenance_dashboard.php`
- `reset_database.php`
- `run_cleanup_orphan_task_chats.php`
- `run_cleanup_legacy_duplicate_group_chats.php`
- `debug_task_chats.php`
- `debug_groups_type_counts.php`
- `debug_task_title_count.php`

Behavior:

1. Scripts are guarded by environment/access rules.
2. Tenant-safe mode requires `org_id` (query or CLI arg).
3. Global mode is blocked unless explicitly enabled.
4. Dashboard helps run scripts per workspace without manually hunting IDs.

### 3.7 Cross-tenant leak fixes and rating fixes

Important fixes include:

1. Tenant scoping added/strengthened in model queries across tasks, groups, users, chats, notifications, subtasks, feedback.
2. Group creation SQL scope bug fixed (`LIMIT`/scope ordering).
3. Leader feedback SQL scope bug fixed (`LIMIT`/scope ordering).
4. Task card footer rating display corrected:
   - No fake `5.0/5` for new/unrated tasks.
   - Rating shown only when task is completed and has a real task rating.

Files for rating display fix:

- `tasks.php`
- `index.php`
- `my_task.php`

## 4. Current workspace workflow (end to end)

## 4.1 Owner/Admin creates workspace

1. Open `signup.php`.
2. Enter:
   - Workspace name
   - Full name
   - Email
3. System creates:
   - Organization row
   - Admin user row linked to organization
   - Organization membership row with `owner`
4. System emails a generated password.

## 4.2 Admin invites employee

1. Admin logs in.
2. Opens `invite-user.php`.
3. Sends invite to employee email.
4. Invite is stored in `workspace_invites` with token and expiry.

## 4.3 Employee joins workspace

1. Employee opens invite link.
2. Completes join form on `join-workspace.php`.
3. `app/accept-invite.php` creates account in the invite's `organization_id`.
4. Membership row is added in `organization_members`.

## 4.4 Daily usage

1. Users create/view tasks, groups, messages, captures.
2. Queries are tenant-scoped using `organization_id`.
3. Admin reviews submitted tasks and assigns ratings/feedback.

## 5. Super Admin vs Workspace Admin

- Workspace Admin:
  - Manages only one workspace.
  - Can invite employees to that workspace.
  - Sees only that workspace data.

- Super Admin:
  - Intended for system/global maintenance.
  - Not used for normal workspace invite flow from `invite-user.php`.
  - Should be carefully controlled in production.

## 6. Setup guide (local XAMPP/MySQL)

Use this if you are setting up from scratch on localhost.

### 6.1 Prerequisites

1. PHP 8.1+
2. MySQL/MariaDB
3. Apache (XAMPP is fine)
4. SMTP credentials for email sending

### 6.2 Configure environment

1. Copy `.env.example` to `.env.local`.
2. Update values in `.env.local`:
   - `APP_ENV`
   - `APP_URL`
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `MAIL_*`
3. Keep these safe defaults in production:
   - `ALLOW_MAINTENANCE_SCRIPTS=0`
   - `ALLOW_GLOBAL_MAINTENANCE=0` (add this var if not present)

### 6.3 Import base schema/data

Option A: phpMyAdmin import

1. Create/select database.
2. Import `task_management_db_mysql.sql`.

Option B: CLI

```bash
mysql -u your_user -p -e "CREATE DATABASE IF NOT EXISTS your_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u your_user -p your_db < task_management_db_mysql.sql
```

### 6.4 Run multi-tenant foundation migration

Run:

- `sql_add_multi_tenancy_foundation.sql`

You can run all at once, but if legacy schema defaults are invalid, MariaDB may stop on an `ALTER TABLE`.

### 6.5 If you get MariaDB error `#1067 Invalid default value`

You previously hit this for fields like `rated_at` and `expires_at`.

Fix the problematic column default first, then rerun the failed part of the tenant migration.

Example fix pattern:

```sql
ALTER TABLE task_assignees MODIFY rated_at DATETIME NULL DEFAULT NULL;
ALTER TABLE password_resets MODIFY expires_at DATETIME NOT NULL;
```

Then rerun the migration statements that failed.

### 6.6 Run workspace invites migration

Run one of:

1. `sql_create_workspace_invites.sql` (via phpMyAdmin SQL tab)
2. `php run_migration_workspace_invites.php` (CLI/browser, guarded by maintenance rules)

### 6.7 Open app and smoke test

1. Open `http://localhost/task_management_v2/login.php`
2. Create one workspace owner account via `signup.php`
3. Invite an employee
4. Accept invite from join link
5. Confirm employee only sees workspace-local data

## 7. Tenant isolation verification checklist

Create at least 2 workspaces and test this list:

1. Dashboard stats are different per workspace.
2. Users page only shows users in that workspace.
3. Groups page only shows groups in that workspace.
4. Messages/chats only show workspace-local users/groups.
5. Captures page only shows workspace-local screenshots.
6. Tasks page only shows workspace-local tasks.
7. Top Groups/Top Users are workspace-local.
8. New task card does not show rating unless completed and rated.

If any of the above leaks cross-workspace data, treat it as a tenant-scope bug.

## 8. Maintenance dashboard and scripts

Use:

- `maintenance_dashboard.php`

This page lists workspace IDs and provides script links per workspace.

### 8.1 Tenant-safe examples

- Browser: `reset_database.php?org_id=2`
- CLI: `php reset_database.php --org-id=2`
- CLI: `php run_cleanup_orphan_task_chats.php --org-id=2`

### 8.2 Global mode (dangerous)

Requires:

1. `ALLOW_GLOBAL_MAINTENANCE=1`
2. Explicit global flag (`?global=1` or `--global=1`)

Never enable global maintenance in production unless absolutely necessary.

## 9. File map of major SaaS changes

Tenant core:

- `inc/tenant.php`
- `sql_add_multi_tenancy_foundation.sql`

Auth + workspace context:

- `signup.php`
- `app/signup.php`
- `login.php`
- `app/login.php`

Invites/join flow:

- `invite-user.php`
- `app/invite-user.php`
- `app/cancel-invite.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `sql_create_workspace_invites.sql`
- `run_migration_workspace_invites.php`

Maintenance safety:

- `maintenance_guard.php`
- `maintenance_dashboard.php`
- `reset_database.php`
- `run_cleanup_orphan_task_chats.php`
- `run_cleanup_legacy_duplicate_group_chats.php`
- `debug_task_chats.php`
- `debug_groups_type_counts.php`
- `debug_task_title_count.php`

Tenant-aware models (key examples):

- `app/model/Task.php`
- `app/model/Subtask.php`
- `app/model/Group.php`
- `app/model/GroupMessage.php`
- `app/model/Message.php`
- `app/model/Notification.php`
- `app/model/LeaderFeedback.php`
- `app/model/user.php`

Task rating display correction:

- `tasks.php`
- `index.php`
- `my_task.php`

## 10. Known limitations right now

1. Email/username is globally unique across the app.
2. One account is effectively tied to one workspace context at login.
3. No full billing provider integration yet (subscription table is currently manual/foundation).
4. Seat limits are not yet fully enforced in all creation flows.
5. No organization switcher UI for users who belong to multiple workspaces.

## 11. Recommended next milestones

1. Add Stripe/Paddle billing integration using `subscriptions`.
2. Enforce seat limits at invite acceptance.
3. Add audit logs table for security/compliance.
4. Add automated tenant-leak regression tests.
5. Add dedicated super-admin control panel with strong access controls.
6. Add workspace switcher and optional multi-workspace membership support.

## 12. Quick troubleshooting

`Issue`: Employee sees old data from another workspace  
`Check`: Query likely missing tenant scope (`organization_id`)  
`Fix`: Add `tenant_get_scope(...)` in that query's table alias and include params

`Issue`: SQL syntax near `AND organization_id = ?`  
`Check`: Scope appended after `LIMIT`  
`Fix`: Append tenant scope before `LIMIT`, then add `LIMIT` last

`Issue`: Invite link says invalid or expired  
`Check`: `workspace_invites.status` and `expires_at`  
`Fix`: Resend invite from `invite-user.php`

`Issue`: Error `workspace_invites table is missing`  
`Check`: Invite migration not run  
`Fix`: Run `sql_create_workspace_invites.sql` or `run_migration_workspace_invites.php`

`Issue`: MariaDB `#1067 Invalid default value` during migration  
`Check`: Legacy invalid datetime defaults  
`Fix`: Correct affected column default, rerun failed migration statements

## 13. Production safety checklist

1. `APP_ENV=production`
2. `ALLOW_MAINTENANCE_SCRIPTS=0`
3. `ALLOW_GLOBAL_MAINTENANCE=0`
4. HTTPS enabled
5. Strong DB and SMTP credentials
6. `.env.local` not committed
7. Regular DB backups
8. Tenant isolation acceptance tests passed on a staging environment first

---

If you want a second document next, create `README-OPERATIONS.md` for your admin/support team with exact day-to-day support actions (reset per workspace, invite recovery, tenant diagnostics, incident checklist).
