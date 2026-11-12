<?php
declare(strict_types=1);

namespace App;

/** Small helpers for reading the request and sending JSON back. */
final class Http
{
    /** Sends a JSON response and ends the request. */
    public static function send(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');            // never keep API answers in a shared cache
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * CORS: browsers block a page on one address (the React app) from calling
     * an API on another address unless the API says that origin is allowed.
     * Allowed origins come from FRONTEND_ORIGINS (comma-separated), e.g.
     * "https://my-app.vercel.app,http://localhost:5173".
     *
     * This is a rule browsers enforce to protect users. It does not stop
     * non-browser tools from calling the API; the sign-in session protects private data.
     * The deployed frontend reaches the API through its own address (a proxy),
     * so CORS mainly matters for other origins, which get no permission.
     */
    public static function cors(): void
    {
        // An origin never ends with "/", so forgive one typed into the setting.
        $allowed = array_map(
            fn (string $item) => rtrim(trim($item), '/'),
            explode(',', Config::get('FRONTEND_ORIGINS', 'http://localhost:5173'))
        );
        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            header('Access-Control-Max-Age: 86400');
        }

        // Before some requests the browser first asks "may I?" with OPTIONS.
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /** The JSON body of a POST request as an array. */
    public static function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new ApiError(400, 'The request body must be valid JSON.');
        }
        return $data;
    }

    /** A text value from the query string (?name=value), trimmed. */
    public static function query(string $name, string $default = ''): string
    {
        $value = $_GET[$name] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /** A whole number from the query string, kept inside [$min, $max]. Null if absent or not a number. */
    public static function queryInt(string $name, int $min, int $max): ?int
    {
        $value = self::query($name);
        if ($value === '' || !preg_match('/^\d{1,9}$/', $value)) {
            return null;
        }
        return max($min, min($max, (int) $value));
    }

    /** A comma-separated list from the query string: ?platform=Steam,GOG -> ['Steam', 'GOG'] */
    public static function queryList(string $name): array
    {
        $items = array_filter(array_map('trim', explode(',', self::query($name))), fn ($item) => $item !== '');
        return array_slice(array_values(array_unique($items)), 0, 20);
    }

    /**
     * The visitor's network address. Behind Render's and Vercel's proxies the
     * real one is the first entry of X-Forwarded-For. A caller who talks to the
     * backend directly can fake that header, so limits based on it are a
     * second line of defence, never the only one.
     */
    public static function clientIp(): string
    {
        $forwarded = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0]);
        $ip        = filter_var($forwarded, FILTER_VALIDATE_IP) ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return substr($ip, 0, 45);
    }

    /**
     * POST requests must be sent as JSON. Besides keeping input handling simple,
     * this blocks a classic attack: another website cannot make a visitor's
     * browser send a JSON request with their cookie (forms cannot send JSON).
     */
    public static function requireJsonForPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($type !== 'application/json') {
            throw new ApiError(415, 'Send POST requests with Content-Type: application/json.');
        }
    }
}
