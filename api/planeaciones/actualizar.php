<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || !hash_equals(
        $_SESSION['csrf_token'],
        $csrfToken
    )
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$planeacionId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$planeacionId || $planeacionId < 1) {
    $_SESSION['mensaje_error'] =
        'No fue posible identificar la planeación.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$nombre = trim(
    (string) ($_POST['nombre'] ?? '')
);

$fechaInicio = trim(
    (string) ($_POST['fecha_inicio'] ?? '')
);

$fechaFin = trim(
    (string) ($_POST['fecha_fin'] ?? '')
);

$objetivo = trim(
    (string) ($_POST['objetivo'] ?? '')
);

$observaciones = trim(
    (string) ($_POST['observaciones'] ?? '')
);

$_SESSION['datos_edicion_planeacion'] = [
    'id' => $planeacionId,
    'nombre' => $nombre,
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'objetivo' => $objetivo,
    'observaciones' => $observaciones,
];

/*
|--------------------------------------------------------------------------
| Validar fecha
|--------------------------------------------------------------------------
*/

function fechaEditarPlaneacionValida(
    string $fecha,
    bool $opcional = false
): bool {
    if ($fecha === '') {
        return $opcional;
    }

    $fechaConvertida = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $fechaConvertida !== false
        && $fechaConvertida->format('Y-m-d') === $fecha;
}

$errores = [];

if ($nombre === '') {
    $errores[] =
        'Debes escribir el nombre de la planeación.';
}

if (mb_strlen($nombre) > 150) {
    $errores[] =
        'El nombre es demasiado largo.';
}

if (!fechaEditarPlaneacionValida($fechaInicio)) {
    $errores[] =
        'La fecha de inicio no es válida.';
}

if (
    !fechaEditarPlaneacionValida(
        $fechaFin,
        true
    )
) {
    $errores[] =
        'La fecha de finalización no es válida.';
}

if (
    $fechaInicio !== ''
    && $fechaFin !== ''
    && $fechaFin < $fechaInicio
) {
    $errores[] =
        'La fecha final no puede ser anterior a la fecha de inicio.';
}

if (mb_strlen($objetivo) > 500) {
    $errores[] =
        'El objetivo no puede superar 500 caracteres.';
}

if (mb_strlen($observaciones) > 3000) {
    $errores[] =
        'Las observaciones son demasiado largas.';
}

$rutaEdicion =
    BASE_URL
    . '/planeaciones/editar.php?id='
    . $planeacionId;

if (!empty($errores)) {

    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . $rutaEdicion);
    exit;
}

$fechaFin = $fechaFin !== ''
    ? $fechaFin
    : null;

$objetivo = $objetivo !== ''
    ? $objetivo
    : null;

$observaciones = $observaciones !== ''
    ? $observaciones
    : null;

try {

    $consulta = $pdo->prepare(
        'SELECT
            id,
            estado
        FROM planeaciones
        WHERE id = :id
        LIMIT 1'
    );

    $consulta->execute([
        'id' => $planeacionId,
    ]);

    $planeacion = $consulta->fetch();

    if (!$planeacion) {

        unset(
            $_SESSION['datos_edicion_planeacion']
        );

        $_SESSION['mensaje_error'] =
            'La planeación solicitada no existe.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/listar.php'
        );

        exit;
    }

    if (!in_array(
        $planeacion['estado'],
        ['borrador', 'activa'],
        true
    )) {

        unset(
            $_SESSION['datos_edicion_planeacion']
        );

        $_SESSION['mensaje_error'] =
            'La planeación ya no permite modificaciones.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/ver.php?id='
            . $planeacionId
        );

        exit;
    }

    $actualizar = $pdo->prepare(
        'UPDATE planeaciones
        SET
            nombre = :nombre,
            objetivo = :objetivo,
            fecha_inicio = :fecha_inicio,
            fecha_fin = :fecha_fin,
            observaciones = :observaciones
        WHERE id = :id'
    );

    $actualizar->execute([
        'nombre' => $nombre,
        'objetivo' => $objetivo,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'observaciones' => $observaciones,
        'id' => $planeacionId,
    ]);

    unset(
        $_SESSION['datos_edicion_planeacion']
    );

    $_SESSION['mensaje_exito'] =
        'La planeación fue actualizada correctamente.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/ver.php?id='
        . $planeacionId
    );

    exit;

} catch (PDOException $e) {

    error_log(
        'Error al actualizar planeación: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible actualizar la planeación.';

    header('Location: ' . $rutaEdicion);
    exit;
}