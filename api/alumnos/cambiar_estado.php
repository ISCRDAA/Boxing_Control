<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

$alumnoId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$origen = (string) ($_POST['origen'] ?? 'listar');

if (!$alumnoId || $alumnoId < 1) {
    $_SESSION['mensaje_error'] =
        'El alumno seleccionado no es válido.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

try {
    $consulta = $pdo->prepare(
        'SELECT estado
         FROM alumnos
         WHERE id = :id
         LIMIT 1'
    );

    $consulta->execute([
        'id' => $alumnoId,
    ]);

    $alumno = $consulta->fetch();

    if (!$alumno) {
        $_SESSION['mensaje_error'] =
            'El alumno solicitado no existe.';

        header('Location: ' . BASE_URL . '/alumnos/listar.php');
        exit;
    }

    $nuevoEstado = $alumno['estado'] === 'activo'
        ? 'inactivo'
        : 'activo';

    $actualizar = $pdo->prepare(
        'UPDATE alumnos
         SET estado = :estado
         WHERE id = :id'
    );

    $actualizar->execute([
        'estado' => $nuevoEstado,
        'id' => $alumnoId,
    ]);

    $_SESSION['mensaje_exito'] =
        $nuevoEstado === 'activo'
        ? 'El alumno fue activado correctamente.'
        : 'El alumno fue desactivado correctamente.';

    if ($origen === 'ver') {
        header(
            'Location: '
            . BASE_URL
            . '/alumnos/ver.php?id='
            . $alumnoId
        );

        exit;
    }

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
} catch (PDOException $e) {
    error_log(
        'Error al cambiar estado: ' . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible cambiar el estado del alumno.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}