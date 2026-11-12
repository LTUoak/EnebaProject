<?php
declare(strict_types=1);

namespace App;

/**
 * Sign-in sessions.
 *
 * After sign-in the API creates a token: a small piece of text saying
 * "this is user 7, valid until <date>", plus a signature made with APP_SECRET.
 * Nobody without APP_SECRET can produce a valid signature, so the API can
 * trust the token without storing anything. (The format is a standard JWT.)
 *
 * The token is given to the browser in a cookie marked HttpOnly, which means
 * JavaScript on the page cannot read it. The browser itself sends the cookie
 * back with every request. A malicious script that got onto the page therefore
 * cannot steal the sign-in, which it could if the token sat in localStorage.
 */
final class Auth
{
    private const COOKIE           = 'session';
    private const LIFETIME_SECONDS = 7 * 24 * 60 * 60; // 7 days

    /** Signs the user in: sends the session cookie with this response. */
    public static function startSession(array $user): void
    {
        $header  = self::encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encode([
            'sub'   => (int) $user['id'],
            'email' => $user['email'],
            'exp'   => time() + self::LIFETIME_SECONDS,
        ]);
        self::sendCookie("$header.$payload." . self::sign("$header.$payload"), time() + self::LIFETIME_SECONDS);
    }

    /** Signs the user out: tells the browser to delete the cookie. */
    public static function endSession(): void
    {
        self::sendCookie('', time() - 3600);
    }

    /** The signed-in user for this request, or a 401 error. Used by protected endpoints. */
    public static function requireUser(): array
    {
        return self::currentUser() ?? throw new ApiError(401, 'Please sign in to continue.');
    }

    /** The signed-in user for this request, or null if nobody is signed in. */
    public static function currentUser(): ?array
    {
        $token  = $_COOKIE[self::COOKIE] ?? null;
        $claims = is_string($token) ? self::verify($token) : null;
        if ($claims === null) {
            return null;
        }

        // The account must still exist and still be the same one.
        $user = Database::one(
            'SELECT id, email, display_name FROM users WHERE id = :id AND lower(email) = lower(:email)',
            [':id' => $claims['sub'], ':email' => $claims['email']]
        );
        return $user === null ? null : self::publicUser($user);
    }

    /** The user fields that are safe to send to the browser (never the password hash). */
    public static function publicUser(array $user): array
    {
        return [
            'id'           => (int) $user['id'],
            'email'        => $user['email'],
            'display_name' => $user['display_name'],
        ];
    }

    private static function sendCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'httponly' => true,            // JavaScript cannot read it
            'secure'   => self::isHttps(), // only sent over HTTPS (off for local http testing)
            'samesite' => 'Lax',           // not sent with requests that other websites make
        ]);
    }

    /** True when the visitor used HTTPS; behind Render's and Vercel's proxies that is reported in a header. */
    private static function isHttps(): bool
    {
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (($_SERVER['HTTPS'] ?? 'off') !== 'off');
    }

    /** Returns the token's contents if the signature is right and it has not expired. */
    private static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;
        if (!hash_equals(self::sign("$header.$payload"), $signature)) {
            return null;
        }
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')) ?: '', true);
        if (
            !is_array($claims)
            || !is_int($claims['sub'] ?? null)
            || !is_string($claims['email'] ?? null)
            || !is_int($claims['exp'] ?? null)
            || $claims['exp'] < time()
        ) {
            return null;
        }
        return $claims;
    }

    private static function sign(string $text): string
    {
        $secret = Config::get('APP_SECRET') ?? '';
        if (strlen($secret) < 32) {
            throw new \RuntimeException('APP_SECRET is missing or shorter than 32 characters');
        }
        return self::base64Url(hash_hmac('sha256', $text, $secret, true));
    }

    private static function encode(array $data): string
    {
        return self::base64Url(json_encode($data));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
