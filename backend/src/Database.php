<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * One shared database connection per request, opened on first use.
 * Uses DATABASE_URL (Neon's pooled connection, meant for applications).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $url   = Config::get('DATABASE_URL') ?? throw new \RuntimeException('DATABASE_URL is not set');
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'], $parts['user'], $parts['path'])) {
            throw new \RuntimeException('DATABASE_URL is not a valid connection URL');
        }
        parse_str($parts['query'] ?? '', $query);

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $parts['host'],
            $parts['port'] ?? 5432,
            ltrim($parts['path'], '/'),
            $query['sslmode'] ?? 'require'
        );

        return self::$pdo = new PDO($dsn, urldecode($parts['user']), urldecode($parts['pass'] ?? ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // PDO fills the values into the SQL itself (safely escaped) instead of
            // asking the server to prepare each statement. This works through any
            // connection pooler and lets one named value be used twice in a query.
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);
    }

    /** Runs a query with values and returns all rows. */
    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /** Runs a query with values and returns the first row, or null. */
    public static function one(string $sql, array $params = []): ?array
    {
        return self::all($sql, $params)[0] ?? null;
    }
}
