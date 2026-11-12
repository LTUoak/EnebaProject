<?php
declare(strict_types=1);

namespace App\Controllers;

use App\ApiError;
use App\Auth;
use App\Database;
use App\Http;

/** Sign-up, sign-in, sign-out and "who am I". The session travels in an HttpOnly cookie (see Auth). */
final class AuthController
{
    private const MIN_PASSWORD_LENGTH = 8;

    // Sign-in attempt limits: counted over the last LOCK_MINUTES minutes.
    private const LOCK_MINUTES           = 15;
    private const MAX_FAILURES_PER_EMAIL = 5;
    private const MAX_FAILURES_PER_IP    = 30;

    /** POST /api/auth/register — body: {email, password, display_name}. Signs the new user in and returns {user}. */
    public static function register(): array
    {
        $body     = Http::body();
        $email    = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';
        $name     = is_string($body['display_name'] ?? null) ? trim($body['display_name']) : '';

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Use at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        } elseif (strlen($password) > 200) {
            $errors['password'] = 'Use at most 200 characters.';
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            $errors['display_name'] = 'Enter a name between 2 and 50 characters.';
        }
        if ($errors !== []) {
            throw new ApiError(422, 'Please correct the highlighted fields.', $errors);
        }

        try {
            $user = Database::one(
                'INSERT INTO users (email, password_hash, display_name)
                 VALUES (:email, :hash, :name)
                 RETURNING id, email, display_name',
                [':email' => $email, ':hash' => password_hash($password, PASSWORD_DEFAULT), ':name' => $name]
            );
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') { // unique violation: the email is taken
                throw new ApiError(409, 'An account with this email already exists.', [
                    'email' => 'This email is already registered.',
                ]);
            }
            throw $e;
        }

        Auth::startSession($user);
        Http::send(['user' => Auth::publicUser($user)], 201);
    }

    /** POST /api/auth/login — body: {email, password}. Sets the session cookie and returns {user}. */
    public static function login(): array
    {
        $body     = Http::body();
        $email    = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        // Slow down password guessing: after too many recent failures for this
        // email, or from this network address, refuse before even checking the
        // password. The address limit is higher because many people can share one.
        $emailKey = mb_substr(mb_strtolower($email), 0, 254);
        $ip       = Http::clientIp();
        $recent   = Database::one(
            "SELECT count(*) FILTER (WHERE email = :email) AS by_email,
                    count(*) FILTER (WHERE ip = :ip)       AS by_ip
             FROM login_attempts
             WHERE attempted_at > now() - interval '" . self::LOCK_MINUTES . " minutes'
               AND (email = :email OR ip = :ip)",
            [':email' => $emailKey, ':ip' => $ip]
        );
        if ($recent['by_email'] >= self::MAX_FAILURES_PER_EMAIL || $recent['by_ip'] >= self::MAX_FAILURES_PER_IP) {
            header('Retry-After: ' . self::LOCK_MINUTES * 60);
            throw new ApiError(429, 'Too many failed sign-in attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.');
        }

        $user = Database::one(
            'SELECT id, email, display_name, password_hash FROM users WHERE lower(email) = lower(:email)',
            [':email' => $email]
        );

        // The same message for "no such account" and "wrong password", so the
        // API does not reveal which emails are registered.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            Database::all('INSERT INTO login_attempts (email, ip) VALUES (:email, :ip)', [':email' => $emailKey, ':ip' => $ip]);
            Database::all("DELETE FROM login_attempts WHERE attempted_at < now() - interval '1 day'"); // housekeeping
            throw new ApiError(401, 'Incorrect email or password.');
        }

        // A correct password wipes this email's failures.
        Database::all('DELETE FROM login_attempts WHERE email = :email', [':email' => $emailKey]);
        Auth::startSession($user);
        return ['user' => Auth::publicUser($user)];
    }

    /** POST /api/auth/logout — deletes the session cookie. */
    public static function logout(): array
    {
        Auth::endSession();
        return ['signed_out' => true];
    }

    /**
     * GET /api/auth/me — {user: {...}} when signed in, {user: null} when not.
     * The frontend asks this on every page load, so "nobody is signed in" is a
     * normal answer here and not an error.
     */
    public static function me(): array
    {
        return ['user' => Auth::currentUser()];
    }
}
