<?php
/**
 * Migration runner.
 *
 * Usage (from the repo root):  php database/migrate.php
 *
 * Runs every .sql file in database/migrations in numeric order, once.
 * Applied files are recorded in the schema_migrations table, so running
 * the script again only applies files that are new.
 *
 * Connects with DATABASE_URL_UNPOOLED from backend/.env (a direct
 * connection, which schema changes need). Never prints the connection string.
 */

declare(strict_types=1);

require __DIR__ . '/connection.php';

const MIGRATIONS_DIR = __DIR__ . '/migrations';

$db = connect();

$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename   TEXT        PRIMARY KEY,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
    )'
);

$applied = $db->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(MIGRATIONS_DIR . '/*.sql');
sort($files, SORT_STRING);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "skip   $name (already applied)\n";
        continue;
    }

    // The file and its schema_migrations row are saved together: if the SQL
    // fails, nothing from that file is kept and it is not marked as applied.
    $db->beginTransaction();
    try {
        $db->exec(file_get_contents($file));
        $record = $db->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
        $record->execute([$name]);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        fail("$name failed and was rolled back: " . $e->getMessage());
    }

    echo "apply  $name\n";
    $ran++;
}

echo $ran === 0 ? "Nothing to do: database is up to date.\n" : "Done: applied $ran migration(s).\n";
