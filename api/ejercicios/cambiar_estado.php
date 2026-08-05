<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar ejercicio
|--------------------------------------------------------------------------
*/

$ejercicioId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$ejercicioId || $ejercicioId < 1) {
    $_SESSION['mensaje_error'] =
        'El ejercicio seleccionado no es válido.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

try {
    $consulta = $pdo->prepare(
        'SELECT
            id,
            nombre,
            activo
        FROM ejercicios
        WHERE id = :id
        LIMIT 1'
    );

    $consulta->execute([
        'id' => $ejercicioId,
    ]);

    $ejercicio = $consulta->fetch();

    if (!$ejercicio) {
        $_SESSION['mensaje_error'] =
            'El ejercicio solicitado no existe.';

        header('Location: ' . BASE_URL . '/ejercicios/listar.php');
        exit;
    }

    $nuevoEstado = (int) $ejercicio['activo'] === 1
        ? 0
        : 1;

    $actualizar = $pdo->prepare(
        'UPDATE ejercicios
        SET activo = :activo
        WHERE id = :id'
    );

    $actualizar->execute([
        'activo' => $nuevoEstado,
        'id' => $ejercicioId,
    ]);

    $_SESSION['mensaje_exito'] =
        $nuevoEstado === 1
        ? 'El ejercicio "' . $ejercicio['nombre']
            . '" fue activado correctamente.'
        : 'El ejercicio "' . $ejercicio['nombre']
            . '" fue desactivado correctamente.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
} catch (PDOException $e) {
    error_log(
        'Error al cambiar estado del ejercicio: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible cambiar el estado del ejercicio.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}