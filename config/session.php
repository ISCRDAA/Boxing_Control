<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Ruta base del proyecto
|--------------------------------------------------------------------------
|
| Como el proyecto está dentro de htdocs/gym_box, la ruta base es /gym_box.
| Cuando lo subamos a Hostinger revisaremos esta configuración.
|
*/

const BASE_URL = '/Boxing_Control';

/*
|--------------------------------------------------------------------------
| Configuración segura de sesiones
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $usaHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $usaHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Token CSRF
|--------------------------------------------------------------------------
|
| Sirve para comprobar que los formularios fueron enviados desde nuestro
| propio sistema.
|
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Comprobar si existe una sesión iniciada
|--------------------------------------------------------------------------
*/

function usuarioAutenticado(): bool
{
    return isset($_SESSION['usuario']['id']);
}

/*
|--------------------------------------------------------------------------
| Proteger páginas privadas
|--------------------------------------------------------------------------
*/

function requerirSesion(): void
{
    if (!usuarioAutenticado()) {
        $_SESSION['mensaje_error'] = 'Debes iniciar sesión para continuar.';

        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Evitar que un usuario autenticado vuelva al login
|--------------------------------------------------------------------------
*/

function redirigirSiEstaAutenticado(): void
{
    if (usuarioAutenticado()) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Obtener los datos del usuario actual
|--------------------------------------------------------------------------
*/

function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}