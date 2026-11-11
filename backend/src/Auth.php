<?php
declare(strict_types=1);

namespace App;

/**
 * Sign-in tokens.
 *
 * After sign-in the API gives the browser a token: a small piece of text
 * saying "this is user 7, valid until <date>", plus a signature made with
 * APP_SECRET. The browser sends it back with every protected request.
 * Nobody without APP_SECRET can produce a valid signature, so the API can
 * trust the token without storing anything. (The format is a standard JWT.)
 */
final class Auth
{
    private const LIFETIME_SECONDS = 7 * 24 * 60 * 60; // 7 days

    public static function issueToken(array $user): string
    {
        $header  = self::encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encode([
            'sub'   => (int) $user['id'],
            'email' => $user['email'],
            'exp'   => time() + self::LIFETIME_SECONDS,
        ]);
        return "$header.$payload." . self::sign("$header.$payload");
    }

    /** The signed-in user for this request, or a 401 error. */
    public static function requireUser(): array
    {
        $token  = Http::bearerToken();
        $claims = $token !== null ? self::verify($token) : null;
        if ($claims === null) {
            throw new ApiError(401, 'Please sign in to continue.');
        }

        // The account must still exist and still be the same one.
        $user = Database::one(
            'SELECT id, email, display_name FROM users WHERE id = :id AND lower(email) = lower(:email)',
            [':id' => $claims['sub'], ':email' => $claims['email']]
        );
        if ($user === null) {
            throw new ApiError(401, 'Please sign in to continue.');
        }
        return self::publicUser($user);
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
