# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WorkTrack is a web-based project tracking system for managing clients, projects, and workflows. It provides calendar, kanban, and gantt views with drag-and-drop functionality.

**Tech Stack:** PHP 8.0+, SQLite 3, vanilla JavaScript (ES6), HTML5/CSS3

## Development Commands

```bash
# Initialize database (creates tables and default data)
php database/init.php

# Start development server
php -S localhost:8000

# Access SQLite database directly
sqlite3 database/work_track.db
```

**Default login:** `admin` / `admin` at `http://localhost:8000/login.php`

## Architecture

### Database Layer
- **Singleton pattern** via `Database::getInstance()` in [db.php](includes/db.php)
- All queries use PDO prepared statements
- Helper methods: `query()`, `insert()`, `update()`, `delete()`, `fetchAll()`, `fetchOne()`
- Transaction support: `beginTransaction()`, `commit()`, `rollback()`

### Models (`includes/models/`)
Each model provides static-like methods through the Database singleton:
- **Client.php**: `getAll()`, `getById()`, `create()`, `update()`, `delete()`, `search()`
- **Project.php**: `getAll($filters)`, `getCalendarData($start, $end)`, `updateDates()`, `updateStatus()`, `getAttachments()`, `createFromTemplate()`
- **ProjectStatus.php**: `getAll()`, `reorder($statusIds)`, `getUsageStats()`
- **ProjectTemplate.php**: `getAll()`, `getDefault()`, `duplicate()`
- **Settings.php**: Key-value store with `get($key, $default)`, `set($key, $value)`

### API Endpoints (`api/`)
JSON-based endpoints for AJAX operations:
| Endpoint | Purpose |
|----------|---------|
| `get_project.php` | Fetch project details for modal editing |
| `update_project.php` | Update project fields |
| `update_project_date.php` | Calendar drag-drop date changes |
| `update_project_status.php` | Kanban drag-drop status changes |
| `upload_file.php`, `download_file.php`, `delete_file.php` | Attachment handling |
| `ical.php` | iCal/ICS feed generation |
| `quick_client.php` | Create client from project modal |

### Authentication
- Session-based auth in [auth.php](includes/auth.php)
- Call `requireLogin()` at top of protected pages
- `logAudit($table, $recordId, $action, $changes)` for audit trail
- Sessions stored in `sessions/` directory

### Page Templates
- [header.php](includes/header.php): Navigation, CSS includes, session checks
- [footer.php](includes/footer.php): JS includes, closing tags
- [helpers.php](includes/helpers.php): `baseUrl()` for subdirectory-aware URLs

## Database Schema

Core tables in [schema.sql](database/schema.sql):
- `users`: Authentication (username, password_hash, last_login)
- `clients`: Contact info (name, address, phone, email, remarks)
- `projects`: Core data with foreign keys to clients, statuses, templates
- `project_statuses`: Customizable statuses with color and sort_order
- `project_templates`: Reusable project templates
- `project_attachments`: File uploads linked to projects
- `audit_log`: Change tracking (table_name, record_id, action, changes_json)
- `settings`: Key-value application settings

Key indexes exist on: `projects(client_id)`, `projects(status_id)`, `projects(start_date, completion_date)`

## Key Patterns

### Adding a New Model
1. Create class in `includes/models/`
2. Get database via `$db = Database::getInstance()`
3. Use `$db->fetchAll()`, `$db->insert()`, etc.
4. Call `logAudit()` for create/update/delete operations

### Adding an API Endpoint
1. Create file in `api/`
2. Include auth check: `require_once '../includes/auth.php'; requireLogin();`
3. Return JSON: `header('Content-Type: application/json'); echo json_encode($response);`
4. Use HTTP status codes for errors

### Adding a Page
1. Create `.php` file in root
2. Include header/footer: `require_once 'includes/header.php';`
3. Use `baseUrl()` from helpers.php for all internal links
4. Add navigation link in header.php if needed

## Directories Requiring Write Permission

- `database/` - SQLite database
- `uploads/` - Project file attachments (organized by `project_{id}/`)
- `backups/` - Database backup files
- `sessions/` - PHP session storage
