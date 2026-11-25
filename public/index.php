<?php

declare(strict_types=1);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$config = require dirname(__DIR__) . '/app/Config/config.php';
date_default_timezone_set($config['timezone']);

// Simple PSR-4 autoloader for App\*
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Controllers\AdminController;
use App\Controllers\GuestController;
use App\Core\Database;
use App\Core\Helpers;
use App\Core\Router;
use App\Models\AdminModel;
use App\Models\GuestModel;

try {
    $pdo = Database::getConnection($config);
} catch (Throwable $e) {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (str_starts_with($reqPath, '/api/')) {
        Helpers::json([
            'success' => false,
            'message' => 'Koneksi database gagal. Pastikan layanan database sudah berjalan.',
        ], 500);
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>500 - Gangguan Server</title></head>';
    echo '<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#0f172a;">';
    echo '<div style="text-align:center;padding:2rem;"><h1>500</h1><p>Koneksi database gagal. Pastikan container database sudah berjalan.</p></div></body></html>';
    exit;
}
$guestModel = new GuestModel($pdo);
$adminModel = new AdminModel($pdo);

$guestController = new GuestController($guestModel, $config);
$adminController = new AdminController($adminModel, $guestModel, $config);

$router = new Router();

// Public Guest Routes
$router->get('/', fn() => $guestController->index());
$router->post('/api/checkin', fn() => $guestController->store());
$router->get('/checkout', fn() => $guestController->checkoutPage());
$router->post('/api/checkout', fn() => $guestController->checkoutProcess());
$router->get('/photo', fn() => $guestController->servePhoto());

// Admin Routes
$router->get('/admin/login', fn() => $adminController->loginForm());
$router->post('/admin/login', fn() => $adminController->loginSubmit());
$router->get('/admin/logout', fn() => $adminController->logout());
$router->get('/admin', fn() => $adminController->dashboard());
$router->post('/admin/guest/checkout', fn() => $adminController->checkoutGuest());
$router->post('/admin/guest/delete', fn() => $adminController->deleteGuest());
$router->get('/admin/export', fn() => $adminController->exportCsv());
$router->get('/admin/settings', fn() => $adminController->settingsForm());
$router->post('/admin/settings', fn() => $adminController->settingsUpdate());

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
