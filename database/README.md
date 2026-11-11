# Database

- `migrations/` — schema migrations, plain SQL for PostgreSQL (Neon); run them in numeric order
- `migrate.php` — applies the migrations that have not run yet: `php database/migrate.php` from the repo root. It reads `DATABASE_URL_UNPOOLED` from `backend/.env` and records applied files in the `schema_migrations` table, so rerunning is safe.
- `seeds/` — sample data as PHP arrays: `users.php` (sellers and buyers), `products.php` (products with their offers), `banners.php`
- `seed.php` — loads the sample data: `php database/seed.php` from the repo root. It only fills an empty database; `php database/seed.php --reset` deletes all data first and seeds again. It also generates fake keys and past orders. The demo buyer is `demo@example.com`; its password is `SEED_DEMO_PASSWORD` in `backend/.env` (generated on first run).
- `connection.php` — shared by both scripts: reads `backend/.env` and opens the connection
