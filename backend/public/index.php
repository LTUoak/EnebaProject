<?php
/**
 * The single entry point of the API. Every request comes here first.
 *
 *   1. load classes from src/ when they are first used
 *   2. turn any error into a JSON response
 *   3. answer CORS (lets the React app's address call this API)
 *   4. find the route that matches the address and run its controller
 *
 * Run locally from the repo root:
 *   php -S localhost:8000 -t backend/public backend/public/index.php
 */

declare(strict_types=1);

use App\ApiError;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Http;
use App\Router;

// 1. Class loading: App\Controllers\HomeController -> src/Controllers/HomeController.php
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});

// 2. Errors. ApiError carries a message meant for the user. Anything else is a
//    bug or an outage: log the details on the server, tell the browser nothing specific.
set_exception_handler(function (Throwable $e): void {
    if ($e instanceof ApiError) {
        $error = ['message' => $e->getMessage()];
        if ($e->fields !== []) {
            $error['fields'] = $e->fields;
        }
        Http::send(['error' => $error], $e->status);
    }
    error_log(sprintf('%s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
    Http::send(['error' => ['message' => 'Something went wrong on the server. Please try again.']], 500);
});
set_error_handler(function (int $level, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $level, $file, $line);
});

// 3. CORS, and the rule that POST requests must be JSON
Http::cors();
Http::requireJsonForPost();

// 4. Routes: every address the API answers, in one place.
$router = new Router();

$router->get('/api/health', fn () => ['status' => 'ok']);

// Homepage blocks, one endpoint each
$router->get('/api/home/banners',      HomeController::banners(...));
$router->get('/api/home/best-sellers', HomeController::bestSellers(...));
$router->get('/api/home/deals',        HomeController::deals(...));

// Store and product page
$router->get('/api/products',          ProductController::index(...));
$router->get('/api/products/filters',  ProductController::filters(...));
$router->get('/api/products/{slug}',   ProductController::show(...));

// Accounts
$router->post('/api/auth/register',    AuthController::register(...));
$router->post('/api/auth/login',       AuthController::login(...));
$router->post('/api/auth/logout',      AuthController::logout(...));
$router->get('/api/auth/me',           AuthController::me(...));

// Cart (lives in the browser; this only prices it)
$router->post('/api/cart/quote',       CartController::quote(...));

// Checkout and order history (sign-in required)
$router->post('/api/orders',           OrderController::create(...));
$router->get('/api/orders',            OrderController::index(...));
$router->get('/api/orders/{id}',       OrderController::show(...));

$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
