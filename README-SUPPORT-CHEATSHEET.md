# TaskFlow SaaS Support Cheatsheet

This is a practical day-to-day guide for admins/support staff.

Use this when handling:

1. Invite issues
2. Wrong workspace access issues
3. Data visibility/isolation issues
4. Tenant-safe maintenance actions

For full architecture and setup details, read `README-SAAS.md`.

## 1. Safety rules (always follow)

1. Never run global reset on production.
2. Prefer tenant-safe actions using `org_id`.
3. Keep these in production:
   - `ALLOW_MAINTENANCE_SCRIPTS=0`
   - `ALLOW_GLOBAL_MAINTENANCE=0`
4. Never share raw password hashes or `.env.local` values.
5. Take a DB backup before destructive maintenance.

## 2. Quick links (local example)

1. Login: `http://localhost/task_management_v2/login.php`
2. Owner signup: `http://localhost/task_management_v2/signup.php`
3. Invite users: `http://localhost/task_management_v2/invite-user.php`
4. Join workspace: `http://localhost/task_management_v2/join-workspace.php?token=...`
5. Maintenance dashboard: `http://localhost/task_management_v2/maintenance_dashboard.php`
6. Workspace billing: `http://localhost/task_management_v2/workspace-billing.php`

## 3. Quick SQL checks (phpMyAdmin SQL tab)

### 3.1 List workspaces

```sql
SELECT id, name, slug, status, plan_code
FROM organizations
ORDER BY id ASC;
```

### 3.2 Find a user's workspace

```sql
SELECT u.id, u.full_name, u.username, u.role, u.organization_id, o.name AS organization_name
FROM users u
LEFT JOIN organizations o ON o.id = u.organization_id
WHERE u.username = 'employee@example.com';
```

### 3.3 Check workspace membership role

```sql
SELECT om.organization_id, o.name AS organization_name, om.user_id, u.username, om.role
FROM organization_members om
JOIN users u ON u.id = om.user_id
JOIN organizations o ON o.id = om.organization_id
WHERE u.username = 'employee@example.com';
```

### 3.4 Check pending invites for a workspace

```sql
SELECT id, email, full_name, status, expires_at, created_at
FROM workspace_invites
WHERE organization_id = 2
ORDER BY id DESC;
```

### 3.5 Quick leak check by org counts

```sql
SELECT organization_id, COUNT(*) AS task_count
FROM tasks
GROUP BY organization_id
ORDER BY organization_id;

SELECT organization_id, COUNT(*) AS group_count
FROM groups
GROUP BY organization_id
ORDER BY organization_id;

SELECT organization_id, COUNT(*) AS screenshot_count
FROM screenshots
GROUP BY organization_id
ORDER BY organization_id;
```

## 4. Common support scenarios

### 4.1 "Invite email was not received"

Do this:

1. Check `workspace_invites` row exists and status is `pending`.
2. In `invite-user.php`, copy and send the manual invite link.
3. Verify SMTP settings in `.env.local` (`MAIL_*` values).
4. Ask user to check spam folder.

### 4.2 "Invite link says invalid/expired"

Do this:

1. Check token/status/expiry in `workspace_invites`.
2. If expired or revoked, send a new invite.
3. Ensure workspace status is not `suspended` or `canceled`.

### 4.3 "Employee cannot join workspace"

Checklist:

1. Confirm `workspace_invites` migration was run:
   - `sql_create_workspace_invites.sql`
   - or `php run_migration_workspace_invites.php`
2. Confirm invite status is `pending`.
3. Confirm employee email is valid and not already used by another account.

### 4.3.1 "Invalid or expired request" appears on invite/join/billing form

This is usually CSRF token validation (security check), not a database failure.

Do this:

1. Ask user to refresh the current page and submit again.
2. Ask user to avoid submitting from very old tabs.
3. Confirm user is still logged in (for admin forms).
4. Retry from normal UI route:
   - `invite-user.php`
   - `join-workspace.php?token=...`
   - `workspace-billing.php`

### 4.3.2 "Invalid or expired request" appears on task/group/profile/user actions

This is also CSRF protection and is expected when a stale page submits old tokens.

Do this:

1. Refresh the affected page first.
2. Re-run the action from current UI state (do not use old tab history).
3. Re-login if session timed out.
4. Retry endpoint flow from the proper page:
   - Tasks: `tasks.php`, `my_task.php`, `edit-task-employee.php`, `create_task.php`
   - Groups: `groups.php`
   - Profile/User: `edit_profile.php`, `edit-user.php`, `user.php`
   - Messages: `messages.php`
   - Attendance/Capture: `index.php`, `capture.html`
   - Notifications: header bell -> `app/notification-read.php`

### 4.3.3 "Message send/load or time-in/time-out suddenly fails after security hardening"

Possible cause:

1. CSRF token missing from AJAX call (client-side script mismatch or stale file cache).

Do this:

1. Hard refresh browser (Ctrl+F5) and retry.
2. Confirm the user has an active login session.
3. Check browser Network tab and verify request payload includes `csrf_token`.
4. Verify affected endpoint path:
   - Messages: `app/ajax/insert.php`, `app/ajax/insertGroupMessage.php`, `app/ajax/getMessage.php`, `app/ajax/getGroupMessage.php`
   - Attendance: `time_in.php`, `time_out.php`
   - Capture upload: `save_screenshot.php`

### 4.4 "User sees data from another workspace"

Treat as a security bug.

Immediate steps:

1. Capture affected page URL and user account.
2. Confirm user's `organization_id`.
3. Confirm leaked record's `organization_id`.
4. Restrict affected account temporarily if needed.
5. Escalate to developer with evidence (see Section 8).

### 4.5 "New/unrated task shows 5.0/5"

Expected behavior after fix:

1. Card rating is shown only when task is completed and has `task.rating > 0`.
2. New/pending/unrated tasks should not show rating in footer.

If issue appears again, report page + screenshot immediately.

### 4.6 "Admin cannot rate/review submitted task"

Checklist:

1. Verify admin and task are in same `organization_id`.
2. Verify task is in `submitted` state before review.
3. Verify leader/member assignment exists in `task_assignees`.
4. Check PHP error logs for SQL/tenant-scope errors.

### 4.7 "Captures from other workspace are visible"

Checklist:

1. Confirm screenshot rows have correct `organization_id`.
2. Confirm viewing user belongs to current workspace.
3. Run tenant leak audit for screenshot endpoints if needed.

### 4.8 "Need to increase workspace seats"

Preferred method:

1. Open `workspace-billing.php` as workspace admin.
2. Use `Manage Seats (Manual)` and set new seat limit.
3. Save and retry invite/join flow.

Rules:

1. Seat limit cannot be lower than current active members.
2. Super Admin is read-only for seat updates from this page.

## 5. Tenant-safe maintenance actions

Use `maintenance_dashboard.php` when possible.

CLI examples:

```bash
php reset_database.php --org-id=2
php run_cleanup_orphan_task_chats.php --org-id=2
php run_cleanup_legacy_duplicate_group_chats.php --org-id=2
php debug_task_chats.php --org-id=2
php debug_groups_type_counts.php --org-id=2
```

Browser examples:

1. `reset_database.php?org_id=2`
2. `run_cleanup_orphan_task_chats.php?org_id=2`

Global mode:

1. Requires `ALLOW_GLOBAL_MAINTENANCE=1`
2. Requires explicit `global=1`
3. Use only on controlled environments

## 6. Migrations quick reference

### 6.1 Multi-tenant foundation

- `sql_add_multi_tenancy_foundation.sql`

### 6.2 Workspace invites

- `sql_create_workspace_invites.sql`
- `run_migration_workspace_invites.php`

### 6.3 If MariaDB throws `#1067 Invalid default value`

1. Fix the reported legacy column default first.
2. Rerun only the failed migration section.
3. Recheck schema and indexes.

## 7. Incident response mini-checklist

1. What workspace (`org_id`) is affected?
2. Who is affected (user email/id)?
3. What page/action caused the issue?
4. Exact error text or screenshot?
5. Is this cross-tenant data exposure?
6. What immediate containment was done?
7. Was production impacted?

## 8. Escalation template (send to developer)

Copy and fill this:

```text
Issue type:
Environment (local/staging/prod):
Workspace org_id:
User account/email:
Page/endpoint:
Action performed:
Expected result:
Actual result:
Error text/stack trace:
Screenshots:
SQL checks performed:
Temporary workaround applied:
```

## 9. Where to read next

1. `README-SAAS-QUICKSTART.md` for fast setup
2. `README-SAAS.md` for full SaaS concepts and architecture
3. `README-DEPLOY.md` for deployment/security checklist
