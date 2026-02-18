# Storage Management & Cleanup

> **Date:** February 2026
> **Status:** Implemented
> **Location:** Admin Panel > Settings > System Management

---

## Table of Contents

1. [Overview](#1-overview)
2. [Storage Usage Analysis](#2-storage-usage-analysis)
3. [Log File Management](#3-log-file-management)
4. [Cache & Session File Cleanup](#4-cache--session-file-cleanup)
5. [Access Control](#5-access-control)
6. [Architecture](#6-architecture)
7. [Common Scenarios](#7-common-scenarios)
8. [Audit Trail](#8-audit-trail)

---

## 1. Overview

The Storage Management feature provides visibility into disk space usage within the Laravel `storage/` directory and tools to clean up accumulated files. It is integrated into the existing **System Management** page in the admin panel.

### Key Capabilities

- **Visual disk usage breakdown** per storage subdirectory with percentage bars
- **Top 10 largest files** identification across all storage
- **Log file management** with age-based cleanup and full listing
- **Framework file cleanup** for cache, sessions, compiled views, and Livewire temp files
- **Activity logging** for all cleanup actions (audit trail)

### Monitored Directories

| Directory | Contents |
|-----------|----------|
| `storage/app/public` | Uploaded files & public assets (logos, PDF settings) |
| `storage/app/private` | Private application files, Livewire temp uploads |
| `storage/framework/cache` | File-based cache data |
| `storage/framework/sessions` | Session files (when using file driver) |
| `storage/framework/views` | Compiled Blade view templates |
| `storage/framework/testing` | Test artifacts |
| `storage/logs` | Application log files |

---

## 2. Storage Usage Analysis

### What It Shows

- **Total storage usage** with file count displayed prominently
- **Per-directory breakdown table** showing:
  - Directory path
  - Description of contents
  - Size (human-readable)
  - File count
  - Visual progress bar with percentage
- **Color-coded bars**: red (>50%), orange (>25%), blue (normal)
- **Top 10 largest files** across all storage directories

### How to Use

1. Navigate to **Admin > Settings > System Management**
2. The "Storage Usage Analysis" section is at the top and loads automatically
3. Review the breakdown table to identify which directories consume the most space
4. Check the "Top 10 Largest Files" table to find individual large files
5. Click "Refresh Analysis" and reload the page to get updated numbers after cleanup

---

## 3. Log File Management

### What It Shows

- Total number of log files and their combined size
- Per-file listing with:
  - File name
  - Size (color-coded: red >10MB, orange >1MB)
  - Last modified date
  - Age (color-coded: red >30 days, orange >7 days)

### Available Actions (Super Admin Only)

| Action | Description |
|--------|-------------|
| **Clear Old Logs (Keep Today)** | Deletes all log files older than 1 day |
| **Clear Logs by Age** | Choose retention period: all, 1/3/7/14/30 days |
| **Clear All Logs** | Deletes every log file (new ones auto-create on next request) |

### How to Use

1. Open the "Log File Management" section
2. Review which log files exist and their sizes
3. If logs are large or old, use one of the cleanup buttons:
   - For routine maintenance: "Clear Old Logs (Keep Today)"
   - For selective cleanup: "Clear Logs by Age" (choose 7 days recommended)
   - For a full reset: "Clear All Logs"
4. All actions require confirmation before executing
5. After cleanup, reload the page to see updated file listings

### Notes

- `.gitignore` files are never deleted
- A new `laravel.log` file is automatically created on the next request after clearing
- Log rotation is handled by Laravel's logging configuration

---

## 4. Cache & Session File Cleanup

### What It Shows

A grid of 4 cards showing current usage for:

1. **Framework Cache Files** — with current cache driver info
2. **Session Files** — with current session driver info
3. **Compiled Views** — Blade template cache
4. **Livewire Temp Uploads** — leftover upload files

Each card shows the size, file count, and current driver configuration. If a non-file driver is in use (e.g., Redis, database), a note explains that file cleanup may not be applicable.

### Available Actions (Super Admin Only)

| Action | Description | Side Effects |
|--------|-------------|--------------|
| **Clear Cache Files** | Deletes `storage/framework/cache/data/` contents | Cache rebuilds automatically |
| **Clear Session Files** | Deletes `storage/framework/sessions/` contents | All users logged out (file driver only) |
| **Clear Compiled Views** | Deletes `storage/framework/views/` contents | Views recompile on next access |
| **Clear Livewire Temp** | Deletes `storage/app/private/livewire-tmp/` contents | In-progress uploads interrupted |
| **Clear All Framework Files** | All of the above combined | All side effects combined |

### How to Use

1. Open the "Cache & Session File Cleanup" section
2. Review the cards to see which areas use the most space
3. Click individual cleanup buttons for targeted cleanup, or use "Clear All Framework Files" for a complete sweep
4. The "Clear Session Files" button only appears when the session driver is `file`
5. All actions require confirmation

### Important

- **Clear Session Files** will log out all users including yourself if using file sessions
- These files are automatically regenerated, so cleanup is always safe
- For Redis/database cache/session drivers, file cleanup has minimal effect — use the "Cache Management" section instead

---

## 5. Access Control

| Role | View Storage Info | Perform Cleanup Actions |
|------|------------------|------------------------|
| **Super Admin** | Yes | Yes |
| **Admin** | Yes (read-only) | No — action buttons hidden |
| **User** | No access | No access |

- All cleanup actions are protected by the `canModify()` check requiring `isSuperAdmin()`
- Regular admins can see storage usage data but cannot execute any cleanup operations
- The page itself requires admin panel access (`canAccessPanel()`)

---

## 6. Architecture

### Service: `App\Services\StorageAnalyzerService`

The service handles all storage analysis and cleanup logic, keeping it testable and decoupled from the UI.

**Analysis Methods:**
- `getDirectoryBreakdown()` — Per-directory size and file count
- `getTotalUsage()` — Aggregate totals
- `getLogFiles()` — Log file listing with metadata
- `getLargestFiles(int $limit)` — Top N largest files across storage

**Cleanup Methods:**
- `clearOldLogs(int $keepDays)` — Delete logs older than N days
- `clearAllLogs()` — Delete all log files
- `clearFrameworkCacheFiles()` — Clear file cache
- `clearSessionFiles()` — Clear session files
- `clearCompiledViews()` — Clear compiled Blade views
- `clearLivewireTmp()` — Clear Livewire temporary uploads

**Utility Methods:**
- `getSessionDriver()` — Current session driver
- `getCacheDriver()` — Current cache driver

All cleanup methods return a consistent result array:
```php
['deleted_count' => int, 'freed_bytes' => int, 'freed_human' => string]
```

### Safety Measures

- `.gitignore` files are preserved in all cleanup operations
- Subdirectories are cleaned recursively (CHILD_FIRST order) to properly remove nested structures
- All file operations use `@unlink` / `@rmdir` to gracefully handle permission issues
- Every cleanup action is wrapped in try/catch with user-friendly error notifications

---

## 7. Common Scenarios

### "My storage is suddenly large"

1. Go to **System Management > Storage Usage Analysis**
2. Check which directory has the highest percentage
3. Common culprits:
   - `storage/logs` — Log files growing unbounded
   - `storage/framework/views` — Compiled views accumulating
   - `storage/framework/cache` — File cache growing
   - `storage/app/private` — Leftover Livewire uploads

### "Log files are too large"

1. Check "Log File Management" section for file sizes
2. Use "Clear Logs by Age" with 7-day retention for routine cleanup
3. Consider switching to `daily` log channel in Environment Settings for automatic rotation

### "I want to do a full cleanup"

1. In "Cache & Session File Cleanup", click "Clear All Framework Files"
2. In "Log File Management", click "Clear All Logs"
3. In "Cache Management" (collapsed section), click "Clear All Caches"
4. Optionally run "Optimize Application" to rebuild caches fresh

---

## 8. Audit Trail

Every cleanup action is recorded in the activity log with:
- **Who** performed it (authenticated user)
- **What** was done (action type, file count, space freed)
- **When** it happened (timestamp)

Example log entries:
```
Cleared 12 old log files, freed 45.23 MB
Cleared all 5 log files, freed 128.50 MB
Cleared 847 framework cache files, freed 23.10 MB
Cleared 15 session files, freed 1.20 MB
Cleared all framework files: 1204 files, freed 89.30 MB
```

These can be viewed in the Activity Log section of the admin panel.
