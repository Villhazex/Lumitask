# Lumitask Architecture (MVC v2)

## Folder structure

```
app/
  Core/           # Router, Database, Auth, CSRF, Session, Validator
  Controllers/Api # JSON API endpoints
  Repositories/   # Data access (prepared statements)
  Services/       # Business logic (AuthService, ...)
bootstrap/        # Autoload + Application boot
config/           # app.php, database.php
database/         # schema.sql, migrate.php
public/           # (optional) document root
storage/uploads/  # Task file attachments
api.php           # API front controller
```

## Security

- `password_hash()` / `password_verify()` for credentials
- Prepared statements via `App\Core\Database`
- CSRF token on forms and API (`X-CSRF-TOKEN` header)
- Session cookie: HttpOnly, SameSite
- `session_regenerate_id()` on login
- Soft delete for tasks/lists
- Role-based access: `admin` | `member`

## API (AJAX)

Base: `/Lumitask/api.php?route=/api/...`

| Route | Method | Description |
|-------|--------|-------------|
| `/api/tasks` | GET | List tasks (filter: q, status, prioritas, list_id, due) |
| `/api/tasks` | POST | Create task |
| `/api/tasks/complete` | POST | Mark done |
| `/api/tasks/delete` | POST | Soft delete |
| `/api/dashboard/stats` | GET | Dashboard statistics |
| `/api/notifications` | GET | Deadline & system notifications |
| `/api/activity` | GET | Activity log |
| `/api/collaboration/poll` | GET | Realtime polling |
| `/api/attachments` | POST | Upload file |
| `/api/public/tasks` | * | Collaborative public tasks |

## Setup

1. Import `database/schema.sql` **or** run `php database/migrate.php` on existing DB
2. Ensure Apache `mod_rewrite` enabled for `.htaccess`
3. Hard refresh browser after deploy

## Default admin (fresh schema)

- Username: `admin`
- Password: `password` (change immediately)
