<?php
/**
 * Shared helpers for the database scripts (migrate.php, seed.php):
 * reading backend/.env and opening the database connection.
 * Nothing here ever prints the connection string.
 */

declare(strict_types=1);

const ENV_FILE     = __DIR__ . '/../backend/.env';
const URL_VARIABLE = 'DATABASE_URL_UNPOOLED';

function fail(string $message): never
{
    fwrite(STDERR, "ERROR: $message\n");
    exit(1);
}

/** Reads KEY=value lines from backend/.env. */
function readEnvFile(): array
{
    if (!is_file(ENV_FILE)) {
        fail('Env file not found: backend/.env');
    }
    $values = [];
    foreach (file(ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim(trim($value), "\"'");
    }
    return $values;
}

/** Opens a PDO connection using DATABASE_URL_UNPOOLED from backend/.env. */
function connect(): PDO
{
    $env = readEnvFile();
    if (empty($env[URL_VARIABLE])) {
        fail(URL_VARIABLE . ' is missing from backend/.env');
    }

    $parts = parse_url($env[URL_VARIABLE]);
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
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // The driver's message can contain the host and user name, so only the error code is shown.
        fail('Could not connect to the database (error code ' . $e->getCode() . ').');
    }
}
