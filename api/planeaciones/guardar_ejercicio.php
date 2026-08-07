<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
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

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Recibir identificadores
|--------------------------------------------------------------------------
*/

$planeacionId = filter_var(
    $_POST['planeacion_id'] ?? null,
    FILTER_VALIDATE_INT
);

$ejercicioId = filter_var(
    $_POST['ejercicio_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$planeacionId || $planeacionId < 1) {
    $_SESSION['mensaje_error'] =
        'No fue posible identificar la planeación.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$rutaFormulario =
    BASE_URL
    . '/planeaciones/agregar_ejercicio.php?planeacion_id='
    . $planeacionId;

/*
|--------------------------------------------------------------------------
| Conservar datos si ocurre un error
|--------------------------------------------------------------------------
*/

$_SESSION['datos_planeacion_ejercicio'] = $_POST;

unset(
    $_SESSION['datos_planeacion_ejercicio']['csrf_token']
);

/*
|--------------------------------------------------------------------------
| Recibir campos
|--------------------------------------------------------------------------
*/

$diaSemana = trim(
    (string) ($_POST['dia_semana'] ?? '')
);

$intensidad = trim(
    (string) ($_POST['intensidad'] ?? '')
);

$seriesTexto = trim(
    (string) ($_POST['series'] ?? '')
);

$repeticionesTexto = trim(
    (string) ($_POST['repeticiones'] ?? '')
);

$roundsTexto = trim(
    (string) ($_POST['rounds'] ?? '')
);

$duracionTexto = trim(
    (string) ($_POST['duracion_minutos'] ?? '')
);

$descansoTexto = trim(
    (string) ($_POST['descanso_segundos'] ?? '')
);

$distanciaTexto = trim(
    (string) ($_POST['distancia_metros'] ?? '')
);

$indicaciones = trim(
    (string) ($_POST['indicaciones'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Funciones de validación
|--------------------------------------------------------------------------
*/

function enteroOpcionalPositivo(string $valor): bool
{
    if ($valor === '') {
        return true;
    }

    return filter_var(
        $valor,
        FILTER_VALIDATE_INT
    ) !== false && (int) $valor > 0;
}

function decimalOpcionalPositivo(string $valor): bool
{
    if ($valor === '') {
        return true;
    }

    return is_numeric($valor)
        && (float) $valor > 0;
}

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/

$errores = [];

if (!$ejercicioId || $ejercicioId < 1) {
    $errores[] =
        'Debes seleccionar un ejercicio.';
}

$diasPermitidos = [
    'lunes',
    'martes',
    'miercoles',
    'jueves',
    'viernes',
    'sabado',
    'domingo',
];

if (!in_array(
    $diaSemana,
    $diasPermitidos,
    true
)) {
    $errores[] =
        'El día seleccionado no es válido.';
}

$intensidadesPermitidas = [
    'baja',
    'media',
    'alta',
    'muy_alta',
];

if (!in_array(
    $intensidad,
    $intensidadesPermitidas,
    true
)) {
    $errores[] =
        'La intensidad seleccionada no es válida.';
}

if (!enteroOpcionalPositivo($seriesTexto)) {
    $errores[] =
        'Las series deben ser un número mayor que cero.';
}

if (!enteroOpcionalPositivo($repeticionesTexto)) {
    $errores[] =
        'Las repeticiones deben ser un número mayor que cero.';
}

if (!enteroOpcionalPositivo($roundsTexto)) {
    $errores[] =
        'Los rounds deben ser un número mayor que cero.';
}

if (!decimalOpcionalPositivo($duracionTexto)) {
    $errores[] =
        'La duración debe ser mayor que cero.';
}

if (!enteroOpcionalPositivo($descansoTexto)) {
    $errores[] =
        'El descanso debe ser un número mayor que cero.';
}

if (!decimalOpcionalPositivo($distanciaTexto)) {
    $errores[] =
        'La distancia debe ser mayor que cero.';
}

if (mb_strlen($indicaciones) > 500) {
    $errores[] =
        'Las indicaciones no pueden superar 500 caracteres.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . $rutaFormulario);
    exit;
}

/*
|--------------------------------------------------------------------------
| Preparar campos opcionales
|--------------------------------------------------------------------------
*/

$series = $seriesTexto !== ''
    ? (int) $seriesTexto
    : null;

$repeticiones = $repeticionesTexto !== ''
    ? (int) $repeticionesTexto
    : null;

$rounds = $roundsTexto !== ''
    ? (int) $roundsTexto
    : null;

$duracion = $duracionTexto !== ''
    ? number_format(
        (float) $duracionTexto,
        2,
        '.',
        ''
    )
    : null;

$descanso = $descansoTexto !== ''
    ? (int) $descansoTexto
    : null;

$distancia = $distanciaTexto !== ''
    ? number_format(
        (float) $distanciaTexto,
        2,
        '.',
        ''
    )
    : null;

$indicaciones = $indicaciones !== ''
    ? $indicaciones
    : null;

$usuario = usuarioActual();

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar planeación
    |--------------------------------------------------------------------------
    */

    $consultaPlaneacion = $pdo->prepare(
        'SELECT
            id,
            estado
        FROM planeaciones
        WHERE id = :id
        LIMIT 1'
    );

    $consultaPlaneacion->execute([
        'id' => $planeacionId,
    ]);

    $planeacion = $consultaPlaneacion->fetch();

    if (!$planeacion) {
        unset($_SESSION['datos_planeacion_ejercicio']);

        $_SESSION['mensaje_error'] =
            'La planeación solicitada no existe.';

        header('Location: ' . BASE_URL . '/planeaciones/listar.php');
        exit;
    }

    if (!in_array(
        $planeacion['estado'],
        ['borrador', 'activa'],
        true
    )) {
        unset($_SESSION['datos_planeacion_ejercicio']);

        $_SESSION['mensaje_error'] =
            'Esta planeación ya no permite agregar ejercicios.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/ver.php?id='
            . $planeacionId
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar ejercicio
    |--------------------------------------------------------------------------
    */

    $consultaEjercicio = $pdo->prepare(
        'SELECT
            id,
            nombre,
            activo
        FROM ejercicios
        WHERE id = :id
        LIMIT 1'
    );

    $consultaEjercicio->execute([
        'id' => $ejercicioId,
    ]);

    $ejercicio = $consultaEjercicio->fetch();

    if (!$ejercicio) {
        $_SESSION['mensaje_error'] =
            'El ejercicio seleccionado no existe.';

        header('Location: ' . $rutaFormulario);
        exit;
    }

    if ((int) $ejercicio['activo'] !== 1) {
        $_SESSION['mensaje_error'] =
            'El ejercicio seleccionado se encuentra inactivo.';

        header('Location: ' . $rutaFormulario);
        exit;
    }

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Calcular orden automático dentro del día
    |--------------------------------------------------------------------------
    */

    $consultaOrden = $pdo->prepare(
        'SELECT
            COALESCE(MAX(orden), 0) + 1 AS siguiente_orden

        FROM planeacion_ejercicios

        WHERE planeacion_id = :planeacion_id
            AND dia_semana = :dia_semana'
    );

    $consultaOrden->execute([
        'planeacion_id' => $planeacionId,
        'dia_semana' => $diaSemana,
    ]);

    $resultadoOrden = $consultaOrden->fetch();

    $orden = (int) (
        $resultadoOrden['siguiente_orden'] ?? 1
    );

    /*
    |--------------------------------------------------------------------------
    | Guardar ejercicio
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO planeacion_ejercicios (
            planeacion_id,
            ejercicio_id,
            agregado_por,
            dia_semana,
            orden,
            series,
            repeticiones,
            rounds,
            duracion_minutos,
            descanso_segundos,
            distancia_metros,
            intensidad,
            indicaciones
        ) VALUES (
            :planeacion_id,
            :ejercicio_id,
            :agregado_por,
            :dia_semana,
            :orden,
            :series,
            :repeticiones,
            :rounds,
            :duracion_minutos,
            :descanso_segundos,
            :distancia_metros,
            :intensidad,
            :indicaciones
        )'
    );

    $insertar->execute([
        'planeacion_id' => $planeacionId,
        'ejercicio_id' => $ejercicioId,
        'agregado_por' => $usuario['id'],
        'dia_semana' => $diaSemana,
        'orden' => $orden,
        'series' => $series,
        'repeticiones' => $repeticiones,
        'rounds' => $rounds,
        'duracion_minutos' => $duracion,
        'descanso_segundos' => $descanso,
        'distancia_metros' => $distancia,
        'intensidad' => $intensidad,
        'indicaciones' => $indicaciones,
    ]);

    $pdo->commit();

    unset($_SESSION['datos_planeacion_ejercicio']);

    $_SESSION['mensaje_exito'] =
        'El ejercicio "'
        . $ejercicio['nombre']
        . '" fue agregado correctamente al '
        . ucfirst($diaSemana)
        . '.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/ver.php?id='
        . $planeacionId
    );

    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Error al agregar ejercicio a planeación: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible agregar el ejercicio.';

    header('Location: ' . $rutaFormulario);
    exit;
}