# Migration to MySQL (phpMyAdmin) - Runbook, Rollback, Verification

This runbook is for moving this project from PostgreSQL to MySQL/MariaDB with minimal risk.

Date prepared: February 13, 2026  
Primary import file: `task_management_db_mysql.sql`

## 1. Scope

1. This runbook covers local/XAMPP style deployment and phpMyAdmin import.
2. This runbook assumes code already points to MySQL (`DB_connection.php` and model SQL changes are in place).
3. This runbook avoids destructive in-place migration by using a new database and controlled cutover.

## 2. Pre-Migration Safety Checklist

1. Freeze writes to the app during migration window.
2. Confirm current app backup is available.
3. Backup project files:
   `C:\xampp\htdocs\task_management_v2`
4. Backup current PostgreSQL database dump (keep original `.sql` unchanged).
5. Confirm `task_management_db_mysql.sql` exists in project root.
6. Confirm `.env.local` is backed up before DB credential changes.

## 3. Create New MySQL Database

Use phpMyAdmin:

1. Open phpMyAdmin.
2. Create a new database, example: `task_management_db_mysql`.
3. Set collation to `utf8mb4_unicode_ci`.

Optional SQL:

```sql
CREATE DATABASE IF NOT EXISTS task_management_db_mysql
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## 4. Import Steps (No In-Place Overwrite)

1. Select the new database in phpMyAdmin.
2. Click `Import`.
3. Choose file: `task_management_db_mysql.sql`.
4. Keep default format `SQL`.
5. Execute import.
6. Confirm import finishes without SQL errors.

## 5. Configure App for MySQL

Update `.env.local`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=task_management_db_mysql
DB_USER=root
DB_PASS=
```

If old `PG*` vars exist, keep them for rollback reference, but `DB_*` should be set and correct.

## 6. Post-Import Integrity Checks (Run in phpMyAdmin SQL tab)

### 6.1 Table existence

```sql
SHOW TABLES;
```

Expected key tables include:
`attendance`, `chat_attachments`, `chats`, `group_members`, `group_message_attachments`, `group_message_reads`, `group_messages`, `groups`, `leader_feedback`, `notifications`, `password_resets`, `screenshots`, `subtasks`, `task_assignees`, `tasks`, `users`.

### 6.2 Row counts (expected from current dump)

```sql
SELECT 'attendance' AS t, COUNT(*) AS c FROM attendance
UNION ALL SELECT 'chat_attachments', COUNT(*) FROM chat_attachments
UNION ALL SELECT 'chats', COUNT(*) FROM chats
UNION ALL SELECT 'group_members', COUNT(*) FROM group_members
UNION ALL SELECT 'group_message_attachments', COUNT(*) FROM group_message_attachments
UNION ALL SELECT 'group_message_reads', COUNT(*) FROM group_message_reads
UNION ALL SELECT 'group_messages', COUNT(*) FROM group_messages
UNION ALL SELECT 'groups', COUNT(*) FROM groups
UNION ALL SELECT 'leader_feedback', COUNT(*) FROM leader_feedback
UNION ALL SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL SELECT 'password_resets', COUNT(*) FROM password_resets
UNION ALL SELECT 'screenshots', COUNT(*) FROM screenshots
UNION ALL SELECT 'subtasks', COUNT(*) FROM subtasks
UNION ALL SELECT 'task_assignees', COUNT(*) FROM task_assignees
UNION ALL SELECT 'tasks', COUNT(*) FROM tasks
UNION ALL SELECT 'users', COUNT(*) FROM users;
```

Expected counts:

1. `attendance`: 0
2. `chat_attachments`: 0
3. `chats`: 3
4. `group_members`: 12
5. `group_message_attachments`: 0
6. `group_message_reads`: 3
7. `group_messages`: 3
8. `groups`: 4
9. `leader_feedback`: 3
10. `notifications`: 30
11. `password_resets`: 0
12. `screenshots`: 0
13. `subtasks`: 5
14. `task_assignees`: 5
15. `tasks`: 2
16. `users`: 6

### 6.3 Constraint sanity

```sql
SHOW CREATE TABLE leader_feedback;
SHOW CREATE TABLE task_assignees;
SHOW CREATE TABLE groups;
```

Verify:

1. `leader_feedback` has unique key on `(task_id, leader_id, member_id)`.
2. `task_assignees` has unique key on `(task_id, user_id)`.
3. `groups` has index `idx_groups_task_chat_task_id` on `(type, task_id)`.

### 6.4 Basic app-critical data checks

```sql
SELECT id, username, role FROM users ORDER BY id;
SELECT id, title, status, assigned_to FROM tasks ORDER BY id;
SELECT id, task_id, leader_id, member_id, rating FROM leader_feedback ORDER BY id;
```

## 7. Functional Verification Checklist

1. Login with known admin account works.
2. User search works (case-insensitive).
3. Group search works (case-insensitive).
4. Task list pages load without SQL errors.
5. Leader feedback upsert works (create then update same `(task_id, leader_id, member_id)`).
6. Chat pages load and existing messages appear.
7. Notifications page loads existing notifications.
8. New task creation works.
9. Subtask review flow works.
10. No PHP fatal errors in Apache/PHP logs.

## 8. Rollback Plan (Fast Recovery)

If any blocking issue appears after cutover:

1. Set app back to previous PostgreSQL config in `.env.local`.
2. Restart Apache/PHP process if needed.
3. Confirm login and core pages work on old DB.
4. Keep MySQL DB untouched for investigation.
5. Fix migration issue in MySQL path and retry cutover in a new window.

Rollback trigger examples:

1. Login fails due to DB errors.
2. Core task pages fail.
3. Data mismatch in critical tables.
4. Feedback upsert fails.

## 9. Non-Destructive Rule

1. Do not drop original PostgreSQL database until all checks pass.
2. Do not overwrite old `.env.local` backup.
3. Do not run destructive reset scripts during migration validation.

## 10. Final Sign-Off Checklist

1. All SQL integrity checks pass.
2. All functional checks pass.
3. Logs show no DB syntax/constraint errors.
4. Team confirms cutover acceptance.
5. PostgreSQL kept as rollback source until stable period completes.

