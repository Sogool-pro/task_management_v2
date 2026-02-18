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
- `app/invite-users-bulk.php`
- `app/generate-invite-link.php`
- `app/cancel-invite.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `app/invite_helpers.php`
- `app/invite_bulk_parser.php`
- `sql_create_workspace_invites.sql`
- `run_migration_workspace_invites.php`
- `app/send_email.php` (workspace invite email sender)

Behavior now:

1. Admin can invite employee by email (single invite).
2. Admin can upload a bulk file (`.xlsx`, `.csv`, or text-based `.pdf`) with employee names/emails.
3. Admin can generate a one-time shareable join link.
4. Employee opens `join-workspace.php?token=...`.
5. Employee sets password (and email when using one-time link) and is created inside the correct workspace.
6. Invite/link token is marked accepted after successful join.

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

### 3.8 Seat limit and subscription gating for invites

Implemented in:

- `inc/tenant.php`
- `app/invite-user.php`
- `invite-user.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `app/signup.php`

Behavior now:

1. New workspaces automatically get a subscription row.
2. Invite creation is blocked when:
   - subscription status is blocked, or
   - trial is expired, or
   - seat limit is reached.
3. Invite acceptance re-checks seat and subscription rules (server-side safety).
4. Invite screen shows seat usage (used/limit/left).

### 3.9 Workspace billing/settings page (admin UI)

Implemented in:

- `workspace-billing.php`
- `inc/new_sidebar.php`
- `inc/nav.php`

Behavior now:

1. Admins can open one page to see:
   - workspace status
   - plan code
   - subscription status
   - seats used/left
   - trial end date
   - current billing period end
   - pending invites
2. If workspace cannot accept new members (seats full, trial expired, blocked status), warning is shown clearly.
3. Sidebar now has a `Billing` menu item for admins.

### 3.10 Manual seat update (simple plan upgrade/downgrade control)

Implemented in:

- `workspace-billing.php`
- `app/update-workspace-seat-limit.php`

Behavior now:

1. Workspace admin can update seat limit directly from billing page.
2. Validation prevents setting seats below current active members.
3. Super Admin is read-only on this page for seat updates.
4. Success/error messages are shown directly in billing UI.

### 3.11 CSRF protection for sensitive workspace actions

Implemented in:

- `inc/csrf.php`
- `invite-user.php`
- `app/invite-user.php`
- `app/cancel-invite.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `workspace-billing.php`
- `app/update-workspace-seat-limit.php`
- `create_task.php`
- `app/add-task.php`
- `groups.php`
- `app/add-group.php`
- `app/delete-group.php`
- `tasks.php`
- `tasks_upstream.php`
- `app/admin-review-task.php`
- `app/delete-task.php`
- `edit-task-employee.php`
- `app/update-task-employee.php`
- `my_task.php`
- `app/add-subtask.php`
- `app/review-subtask.php`
- `app/update-subtask-submission.php`
- `app/submit-task-review.php`
- `app/resubmit-task.php`
- `app/rate-leader.php`
- `edit_profile.php`
- `app/update-profile.php`
- `edit-user.php`
- `app/update-user.php`
- `user.php`
- `app/update-user-role.php`
- `login.php`
- `app/login.php`
- `signup.php`
- `app/signup.php`
- `forgot-password.php`
- `app/req-reset-password.php`
- `reset-password.php`
- `app/do-reset-password.php`
- `admin_clock_out.php`
- `messages.php`
- `app/ajax/insert.php`
- `app/ajax/insertGroupMessage.php`
- `app/ajax/getMessage.php`
- `app/ajax/getGroupMessage.php`
- `index.php`
- `time_in.php`
- `time_out.php`
- `capture.html`
- `save_screenshot.php`
- `app/notification.php`
- `app/notification-read.php`

Behavior now:

1. Sensitive forms now include a hidden CSRF token tied to user session.
2. Token is verified server-side before action is allowed.
3. Invalid/expired token requests are blocked safely.
4. Protected actions now include:
   - invite send/revoke/accept
   - manual seat update
   - task create/delete/review
   - group create/delete
   - subtask create/review/submission
   - task submission/resubmission
   - leader rating submission
   - profile and user update forms
   - role update form
   - login, signup, forgot-password, and reset-password forms
   - admin clock-out AJAX action
   - chat message send + read-marking AJAX actions
   - employee time-in/time-out AJAX actions
   - screenshot upload from the capture window
   - notification read/redirect action
5. Repeating AJAX flows (polling/capture) use a non-consuming CSRF validation mode so normal real-time behavior is preserved.

Why this matters (simple):

- Without CSRF protection, a logged-in admin could be tricked into submitting an unwanted action by a malicious page.
- With CSRF tokens, only forms generated by your app are accepted.

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
3. Chooses one of:
   - send single invite by email
   - upload bulk employee file
   - generate one-time shareable join link
4. System stores tokenized records in `workspace_invites` with expiry.

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
- `app/invite-users-bulk.php`
- `app/generate-invite-link.php`
- `app/cancel-invite.php`
- `join-workspace.php`
- `app/accept-invite.php`
- `app/invite_helpers.php`
- `app/invite_bulk_parser.php`
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
4. Seat limits are enforced for invite send and invite acceptance, but should still be reviewed for any future user-creation paths.
5. CSRF protection now covers major write flows (invite, billing, tasks, groups, subtasks, profile/user updates, auth/reset, admin clock-out, chat AJAX, attendance/capture AJAX, and notification-read action); keep applying it for any new POST/AJAX write endpoint you add.
6. No organization switcher UI for users who belong to multiple workspaces.

## 11. Recommended next milestones

1. Add Stripe/Paddle billing integration using `subscriptions`.
2. Add automated security regression tests (tenant scope + CSRF on write endpoints).
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

`Issue`: Bulk upload says no valid rows found  
`Check`: file format and column headers  
`Fix`: use `.xlsx` or `.csv` with columns like `Full Name` and `Email` (PDF parsing is best-effort for text-based PDFs)

`Issue`: Invalid or expired request (CSRF)  
`Check`: form was submitted from stale tab/session or token missing  
`Fix`: refresh page, then submit again from the app form directly  
`Where`: invite, billing, tasks, groups, subtasks, profile/user updates, auth/reset, admin clock-out, messages/chat send+read actions, attendance/capture actions, and notification-read action

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

Support/ops companion guide:

- `README-SUPPORT-CHEATSHEET.md`
