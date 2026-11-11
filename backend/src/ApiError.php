<?php
declare(strict_types=1);

namespace App;

/**
 * Thrown anywhere in the API to stop and answer with an error.
 * index.php turns it into a JSON response: {"error": {"message": ..., "fields": ...}}
 *
 * $status is the HTTP status code (400 bad input, 401 not signed in,
 * 404 not found, 409 conflict, 422 validation failed).
 * $fields optionally maps an input field to what is wrong with it.
 */
final class ApiError extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $fields = [],
    ) {
        parent::__construct($message);
    }
}
