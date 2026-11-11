<?php
declare(strict_types=1);

namespace App\Controllers;

use App\ApiError;
use App\Auth;
use App\Database;
use App\Http;

/** Sign-up, sign-in and "who am I". */
final class AuthController
{
    private const MIN_PASSWORD_LENGTH = 8;

    /** POST /api/auth/register — body: {email, password, display_name}. Returns {token, user}. */
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

        Http::send(['token' => Auth::issueToken($user), 'user' => Auth::publicUser($user)], 201);
    }

    /** POST /api/auth/login — body: {email, password}. Returns {token, user}. */
    public static function login(): array
    {
        $body     = Http::body();
        $email    = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        $user = Database::one(
            'SELECT id, email, display_name, password_hash FROM users WHERE lower(email) = lower(:email)',
            [':email' => $email]
        );

        // The same message for "no such account" and "wrong password", so the
        // API does not reveal which emails are registered.
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            throw new ApiError(401, 'Incorrect email or password.');
        }

        return ['token' => Auth::issueToken($user), 'user' => Auth::publicUser($user)];
    }

    /** GET /api/auth/me — the signed-in user. Lets the frontend check a saved token on page load. */
    public static function me(): array
    {
        return ['user' => Auth::requireUser()];
    }
}
