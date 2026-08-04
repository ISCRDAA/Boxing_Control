<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Vaciar la información de sesión
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Eliminar la cookie de sesión
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {
    $parametrosCookie = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametrosCookie['path'],
        $parametrosCookie['domain'],
        $parametrosCookie['secure'],
        $parametrosCookie['httponly']
    );
}

session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;