# Database

- `migrations/` — schema migrations, plain SQL for PostgreSQL (Neon); run them in numeric order
- `migrate.php` — applies the migrations that have not run yet: `php database/migrate.php` from the repo root. It reads `DATABASE_URL_UNPOOLED` from `backend/.env` and records applied files in the `schema_migrations` table, so rerunning is safe.
- `seeds/` — seed data
