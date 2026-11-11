<?php
declare(strict_types=1);

namespace App;

/**
 * Reads settings. Real environment variables win (that is how Render
 * provides them); locally the values come from backend/.env.
 */
final class Config
{
    private static ?array $file = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        self::$file ??= self::readEnvFile(__DIR__ . '/../.env');
        return self::$file[$key] ?? $default;
    }

    private static function readEnvFile(string $path): array
    {
        $values = [];
        if (!is_file($path)) {
            return $values;
        }
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
}
