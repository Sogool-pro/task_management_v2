# How to Merge Another GitHub Repo Into This Project

This guide shows the exact Git command syntax to pull updates from another GitHub repo and merge them into your current branch.

## Example Target

- Current repo: `task_management`
- Upstream repo to merge from: `https://github.com/Sogool-pro/task_management_v2`
- Upstream remote name: `sogool`
- Local branch: `main`

## 1. Check Current Branch and Status

```powershell
git status --short
git branch --show-current
```

## 2. Add the Other GitHub Repo as a Remote (one-time)

If not added yet:

```powershell
git remote add sogool https://github.com/Sogool-pro/task_management_v2.git
```

Verify remotes:

```powershell
git remote -v
```

## 3. Fetch Latest From That Repo

```powershell
git fetch sogool
```

## 4. Merge Into Your Current Branch

```powershell
git merge --no-ff sogool/main
```

## 5. If Merge Is Blocked by Local Changes

If Git says local files would be overwritten:

Option A (recommended): stash, merge, re-apply stash

```powershell
git stash push -m "temp-before-merge"
git merge --no-ff sogool/main
git stash pop
```

Option B: discard one file and continue (example file)

```powershell
git checkout -- screenshot_debug.log
git merge --no-ff sogool/main
```

If conflict remains for that file and you want incoming version:

```powershell
git checkout --theirs screenshot_debug.log
git add screenshot_debug.log
git commit --no-edit
```

## 6. Verify Divergence

```powershell
git rev-list --left-right --count main...sogool/main
```

Output meaning:

- `X Y`
- `X` = commits only on local `main`
- `Y` = commits only on `sogool/main`
- Merge is synced when `Y` is `0`

## 7. Push Merged Result to Your Origin

```powershell
git push origin main
```

## Quick One-Liner Flow

Use this when remote already exists and working tree is clean:

```powershell
git fetch sogool; git merge --no-ff sogool/main; git push origin main
```
