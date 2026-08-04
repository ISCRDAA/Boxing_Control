<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar el token CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida. Intenta nuevamente.';

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Recibir y validar los datos
|--------------------------------------------------------------------------
*/

$usuario = trim((string) ($_POST['usuario'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    $_SESSION['mensaje_error'] =
        'Debes escribir el usuario y la contraseña.';

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

try {
    $consulta = $pdo->prepare(
        'SELECT
            id,
            nombre,
            usuario,
            password_hash,
            rol,
            activo
         FROM usuarios
         WHERE usuario = :usuario
         LIMIT 1'
    );

    $consulta->execute([
        'usuario' => $usuario,
    ]);

    $datosUsuario = $consulta->fetch();

    $credencialesCorrectas =
        $datosUsuario &&
        (int) $datosUsuario['activo'] === 1 &&
        password_verify(
            $password,
            $datosUsuario['password_hash']
        );

    if (!$credencialesCorrectas) {
        $_SESSION['mensaje_error'] =
            'El usuario o la contraseña son incorrectos.';

        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Evitar fijación de sesión
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => (int) $datosUsuario['id'],
        'nombre' => $datosUsuario['nombre'],
        'usuario' => $datosUsuario['usuario'],
        'rol' => $datosUsuario['rol'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Registrar el último acceso
    |--------------------------------------------------------------------------
    */

    $actualizarAcceso = $pdo->prepare(
        'UPDATE usuarios
         SET ultimo_acceso = NOW()
         WHERE id = :id'
    );

    $actualizarAcceso->execute([
        'id' => $datosUsuario['id'],
    ]);

    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
} catch (PDOException $e) {
    $_SESSION['mensaje_error'] =
        'Ocurrió un error al iniciar sesión.';

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}