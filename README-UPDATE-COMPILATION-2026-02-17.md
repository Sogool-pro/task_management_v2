# Update Compilation - February 17, 2026

This file summarizes the updates completed today in this workspace.

## 1. Attendance/Navigate Reminder Behavior

Updated employee navigation reminder when user is not clocked in:

1. Reminder now shows once, then does not block further navigation.
2. Reminder behavior is scoped per user account.
3. Reminder state resets on logout, so it appears once again on next login.

Files:

- `index.php`
- `inc/new_sidebar.php`

## 2. Idle Presence Modal

Added dashboard idle check modal:

1. Shows after 100 seconds of no activity.
2. Asks if user is still present.
3. User can dismiss and continue.

File:

- `index.php`

## 3. Toast Notification System

Implemented and refined right-side toast notifications:

1. Unified success/error toast style.
2. Improved app-wide use via shared includes.
3. Adjusted size and icon visibility based on feedback.

Files:

- `inc/toast.php`
- `inc/header.php`
- `login.php`
- `signup.php`
- `forgot-password.php`
- `reset-password.php`
- `join-workspace.php`

## 4. Landing/Login Entry Flow

Adjusted first-page behavior and navigation:

1. Default unauthenticated redirect from `index.php` now goes to Landing page.
2. Added "Back to Landing" in Login page and moved it outside the auth card (top-left).

Files:

- `index.php`
- `login.php`

## 5. Landing Page UI and Support Chat

Landing page updates:

1. `Get Started` button theme aligned with system purple theme.
2. Added support chat widget UI on landing page.
3. Chat responses are based on onboarding/support flow from user guide.
4. Added quick support actions (Get Started, Invite Users, Time Tracker, Billing).

Files:

- `landing.php`
- `css/landing.css`

## 6. User Guide Improvements

Created and refined user-facing documentation:

1. Added a dedicated user flow guide.
2. Rewrote guidance in end-user language (menu/button terms instead of file names).

Files:

- `README-USER-GUIDE.md`
- `README.md`

## 7. Super Admin Maintenance Dashboard Redesign

Redesigned `maintenance_dashboard.php` UI:

1. Modern visual style (cards, hierarchy, spacing, accents).
2. New custom Exit placement in top-right (not old copied style).
3. Refined typography and scale to better match requested references.
4. Updated stat icons style.

Plus feature improvements:

1. Workspace search and filters (name/slug, status, plan).
2. Danger Zone accordion for global reset.
3. Action run log (browser-local).
4. Click loading/anti-double-click state for action links.

File:

- `maintenance_dashboard.php`

## 8. Upstream Merge Performed

Merged latest changes from:

- `https://github.com/Sogool-pro/task_management_v2`

Result:

1. Fetched latest `sogool/main`.
2. Merged into local `main`.
3. Restored local working changes afterward.

