<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/planeaciones/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar token CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida.';

    header('Location: ' . BASE_URL . '/planeaciones/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Conservar información
|--------------------------------------------------------------------------
*/

$_SESSION['datos_planeacion'] = $_POST;

unset($_SESSION['datos_planeacion']['csrf_token']);

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$alumnoId = filter_var(
    $_POST['alumno_id'] ?? null,
    FILTER_VALIDATE_INT
);

$nombre = trim(
    (string) ($_POST['nombre'] ?? '')
);

$fechaInicio = trim(
    (string) ($_POST['fecha_inicio'] ?? '')
);

$fechaFin = trim(
    (string) ($_POST['fecha_fin'] ?? '')
);

$estado = trim(
    (string) ($_POST['estado'] ?? '')
);

$objetivo = trim(
    (string) ($_POST['objetivo'] ?? '')
);

$observaciones = trim(
    (string) ($_POST['observaciones'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Validar fechas
|--------------------------------------------------------------------------
*/

function fechaPlaneacionValida(
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

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/

$errores = [];

if (!$alumnoId || $alumnoId < 1) {
    $errores[] = 'Debes seleccionar un alumno.';
}

if ($nombre === '') {
    $errores[] =
        'Debes escribir el nombre de la planeación.';
}

if (mb_strlen($nombre) > 150) {
    $errores[] =
        'El nombre de la planeación es demasiado largo.';
}

if (!fechaPlaneacionValida($fechaInicio)) {
    $errores[] =
        'La fecha de inicio no es válida.';
}

if (!fechaPlaneacionValida($fechaFin, true)) {
    $errores[] =
        'La fecha de finalización no es válida.';
}

if (
    $fechaInicio !== ''
    && $fechaFin !== ''
    && $fechaFin < $fechaInicio
) {
    $errores[] =
        'La fecha de finalización no puede ser anterior '
        . 'a la fecha de inicio.';
}

$estadosPermitidos = [
    'borrador',
    'activa',
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $errores[] =
        'El estado seleccionado no es válido.';
}

if (mb_strlen($objetivo) > 500) {
    $errores[] =
        'El objetivo no puede superar 500 caracteres.';
}

if (mb_strlen($observaciones) > 3000) {
    $errores[] =
        'Las observaciones son demasiado largas.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . BASE_URL . '/planeaciones/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Preparar campos opcionales
|--------------------------------------------------------------------------
*/

$fechaFin = $fechaFin !== ''
    ? $fechaFin
    : null;

$objetivo = $objetivo !== ''
    ? $objetivo
    : null;

$observaciones = $observaciones !== ''
    ? $observaciones
    : null;

$usuario = usuarioActual();

try {
    /*
    |--------------------------------------------------------------------------
    | Comprobar alumno
    |--------------------------------------------------------------------------
    */

    $consultaAlumno = $pdo->prepare(
        'SELECT
            id,
            nombres,
            apellidos,
            estado
        FROM alumnos
        WHERE id = :id
        LIMIT 1'
    );

    $consultaAlumno->execute([
        'id' => $alumnoId,
    ]);

    $alumno = $consultaAlumno->fetch();

    if (!$alumno) {
        $_SESSION['mensaje_error'] =
            'El alumno seleccionado no existe.';

        header('Location: ' . BASE_URL . '/planeaciones/crear.php');
        exit;
    }

    if ($alumno['estado'] !== 'activo') {
        $_SESSION['mensaje_error'] =
            'No puedes crear una planeación para un alumno inactivo.';

        header('Location: ' . BASE_URL . '/planeaciones/crear.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar planeación
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO planeaciones (
            alumno_id,
            creado_por,
            nombre,
            objetivo,
            fecha_inicio,
            fecha_fin,
            estado,
            observaciones
        ) VALUES (
            :alumno_id,
            :creado_por,
            :nombre,
            :objetivo,
            :fecha_inicio,
            :fecha_fin,
            :estado,
            :observaciones
        )'
    );

    $insertar->execute([
        'alumno_id' => $alumnoId,
        'creado_por' => $usuario['id'],
        'nombre' => $nombre,
        'objetivo' => $objetivo,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'estado' => $estado,
        'observaciones' => $observaciones,
    ]);

    $planeacionId = (int) $pdo->lastInsertId();

    unset($_SESSION['datos_planeacion']);

    $_SESSION['mensaje_exito'] =
        'La planeación fue creada correctamente.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/ver.php?id='
        . $planeacionId
    );

    exit;
} catch (PDOException $e) {
    error_log(
        'Error al guardar planeación: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible guardar la planeación.';

    header('Location: ' . BASE_URL . '/planeaciones/crear.php');
    exit;
}