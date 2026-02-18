# TaskFlow Update Log - February 18, 2026

This document summarizes the changes completed today.

## 1. Rating Is Now Required (No Skip Allowed)

### Admin task review
- Enforced required `task rating` and `leader rating` during task acceptance.
- Removed backend fallback that auto-filled missing leader rating.

Files:
- `app/admin-review-task.php`
- `tasks.php`

### Leader subtask review
- Enforced required `performance score` when leader accepts a member subtask.
- Both client-side and server-side validation now block accept without score.

Files:
- `app/review-subtask.php`
- `my_task.php`

---

## 2. Validation Uses Modal Instead of Browser Alert

- Replaced `alert(...)` rating validation prompts with styled in-page modals.
- Reused the same visual style as confirmation modals for consistency.

Files:
- `tasks.php`
- `my_task.php`

---

## 3. Group Delete Now Uses Confirmation Modal

- Replaced `confirm('Delete this group?')` with custom modal confirmation.
- Modal includes group name + cancel/delete actions.

File:
- `groups.php`

---

## 4. Super Admin Restricted to Maintenance Dashboard

### Access behavior
- Super admin now redirects to `maintenance_dashboard.php` on login.
- Super admin is blocked from workspace UI pages and redirected back to maintenance.
- Added safe redirect fallback when headers are already sent (prevents header warning).

Files:
- `app/login.php`
- `inc/new_sidebar.php`
- `inc/nav.php`

### Maintenance dashboard UX updates
- Removed top-right `Exit Maintenance` button.
- Added small logout button with confirmation modal.
- Added restricted-access error modal when redirected from workspace pages.
- Later adjusted logout button to top-right and red theme.

File:
- `maintenance_dashboard.php`

---

## 5. Group Chat @Mentions Added

### New functionality
- Added `@mention` autocomplete in group chat input.
- Added keyboard controls for mention picker:
  - `Up/Down` to navigate
  - `Enter` to select
  - `Esc` to close
- Added click-to-select mention entries.
- Added `@everyone` mention option.
- Mentions are highlighted in rendered group messages.

Files:
- `messages.php`
- `css/chat.css`
- `app/ajax/getGroupMessage.php`
- `app/model/GroupMessage.php`
- `app/ajax/getGroupMembers.php` (new)

### Mention constraints
- Mention list now excludes the current user (cannot mention yourself).

File:
- `app/ajax/getGroupMembers.php`

### Mention UI styling update
- Mention dropdown changed from dark style to white theme.

File:
- `css/chat.css`

---

## Notes

- Syntax checks were run after key PHP edits using `php -l`.
- Existing unrelated project changes were not reverted.
