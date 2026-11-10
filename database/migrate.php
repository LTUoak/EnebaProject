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

const ENV_FILE       = __DIR__ . '/../backend/.env';
const MIGRATIONS_DIR = __DIR__ . '/migrations';
const URL_VARIABLE   = 'DATABASE_URL_UNPOOLED';

/** Reads KEY=value lines from an env file. */
function readEnvFile(string $path): array
{
    if (!is_file($path)) {
        fail("Env file not found: backend/.env");
    }
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim(trim($value), "\"'");
    }
    return $values;
}

/** Opens a PDO connection from a postgres:// URL. */
function connect(string $url): PDO
{
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['host'], $parts['user'], $parts['path'])) {
        fail(URL_VARIABLE . ' is not a valid connection URL.');
    }
    parse_str($parts['query'] ?? '', $query);

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
        $parts['host'],
        $parts['port'] ?? 5432,
        ltrim($parts['path'], '/'),
        $query['sslmode'] ?? 'require'
    );

    try {
        return new PDO($dsn, urldecode($parts['user']), urldecode($parts['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        // The driver's message can contain the host and user name, so only the error code is shown.
        fail('Could not connect to the database (error code ' . $e->getCode() . ').');
    }
}

function fail(string $message): never
{
    fwrite(STDERR, "ERROR: $message\n");
    exit(1);
}

// --- main -------------------------------------------------------------

$env = readEnvFile(ENV_FILE);
if (empty($env[URL_VARIABLE])) {
    fail(URL_VARIABLE . ' is missing from backend/.env');
}
$db = connect($env[URL_VARIABLE]);

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
