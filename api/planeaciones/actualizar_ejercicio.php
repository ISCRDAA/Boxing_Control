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
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$asignacionId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$asignacionId || $asignacionId < 1) {
    $_SESSION['mensaje_error'] =
        'No fue posible identificar el ejercicio asignado.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar asignación actual
|--------------------------------------------------------------------------
*/

$consultaActual = $pdo->prepare(
    'SELECT
        pe.id,
        pe.planeacion_id,
        pe.ejercicio_id,
        pe.dia_semana,
        pe.orden,
        p.estado AS planeacion_estado

    FROM planeacion_ejercicios AS pe

    INNER JOIN planeaciones AS p
        ON p.id = pe.planeacion_id

    WHERE pe.id = :id
    LIMIT 1'
);

$consultaActual->execute([
    'id' => $asignacionId,
]);

$asignacionActual = $consultaActual->fetch();

if (!$asignacionActual) {
    $_SESSION['mensaje_error'] =
        'El ejercicio asignado no existe.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$planeacionId = (int) $asignacionActual['planeacion_id'];

$rutaEdicion =
    BASE_URL
    . '/planeaciones/editar_ejercicio.php?id='
    . $asignacionId;

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$ejercicioId = filter_var(
    $_POST['ejercicio_id'] ?? null,
    FILTER_VALIDATE_INT
);

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

$_SESSION['datos_edicion_planeacion_ejercicio'] = [
    'id' => $asignacionId,
    'ejercicio_id' => $ejercicioId ?: '',
    'dia_semana' => $diaSemana,
    'intensidad' => $intensidad,
    'series' => $seriesTexto,
    'repeticiones' => $repeticionesTexto,
    'rounds' => $roundsTexto,
    'duracion_minutos' => $duracionTexto,
    'descanso_segundos' => $descansoTexto,
    'distancia_metros' => $distanciaTexto,
    'indicaciones' => $indicaciones,
];

/*
|--------------------------------------------------------------------------
| Validaciones auxiliares
|--------------------------------------------------------------------------
*/

function enteroOpcionalConMaximo(
    string $valor,
    int $maximo
): bool {
    if ($valor === '') {
        return true;
    }

    $entero = filter_var(
        $valor,
        FILTER_VALIDATE_INT
    );

    return $entero !== false
        && $entero > 0
        && $entero <= $maximo;
}

function decimalOpcionalConMaximo(
    string $valor,
    float $maximo
): bool {
    if ($valor === '') {
        return true;
    }

    return is_numeric($valor)
        && (float) $valor > 0
        && (float) $valor <= $maximo;
}

function normalizarOrdenDia(
    PDO $pdo,
    int $planeacionId,
    string $dia
): void {
    $consulta = $pdo->prepare(
        'SELECT id
        FROM planeacion_ejercicios
        WHERE planeacion_id = :planeacion_id
            AND dia_semana = :dia
        ORDER BY orden ASC, id ASC'
    );

    $consulta->execute([
        'planeacion_id' => $planeacionId,
        'dia' => $dia,
    ]);

    $ids = $consulta->fetchAll(PDO::FETCH_COLUMN);

    $actualizar = $pdo->prepare(
        'UPDATE planeacion_ejercicios
        SET orden = :orden
        WHERE id = :id'
    );

    foreach ($ids as $indice => $id) {
        $actualizar->execute([
            'orden' => $indice + 1,
            'id' => $id,
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| Validar valores
|--------------------------------------------------------------------------
*/

$errores = [];

if (!$ejercicioId || $ejercicioId < 1) {
    $errores[] = 'Debes seleccionar un ejercicio.';
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

if (!in_array($diaSemana, $diasPermitidos, true)) {
    $errores[] = 'El día seleccionado no es válido.';
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
    $errores[] = 'La intensidad seleccionada no es válida.';
}

if (!enteroOpcionalConMaximo($seriesTexto, 999)) {
    $errores[] =
        'Las series deben estar entre 1 y 999.';
}

if (!enteroOpcionalConMaximo(
    $repeticionesTexto,
    9999
)) {
    $errores[] =
        'Las repeticiones deben estar entre 1 y 9999.';
}

if (!enteroOpcionalConMaximo($roundsTexto, 999)) {
    $errores[] =
        'Los rounds deben estar entre 1 y 999.';
}

if (!decimalOpcionalConMaximo(
    $duracionTexto,
    9999
)) {
    $errores[] =
        'La duración debe ser una cantidad válida.';
}

if (!enteroOpcionalConMaximo(
    $descansoTexto,
    9999
)) {
    $errores[] =
        'El descanso debe estar entre 1 y 9999 segundos.';
}

if (!decimalOpcionalConMaximo(
    $distanciaTexto,
    999999
)) {
    $errores[] =
        'La distancia debe ser una cantidad válida.';
}

if (mb_strlen($indicaciones) > 500) {
    $errores[] =
        'Las indicaciones no pueden superar 500 caracteres.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . $rutaEdicion);
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar ejercicio seleccionado
|--------------------------------------------------------------------------
*/

$consultaEjercicio = $pdo->prepare(
    'SELECT
        id,
        nombre,
        tipo_medicion,
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

    header('Location: ' . $rutaEdicion);
    exit;
}

/*
|--------------------------------------------------------------------------
| Permitir un ejercicio inactivo solo cuando ya era el asignado
|--------------------------------------------------------------------------
*/

if (
    (int) $ejercicio['activo'] !== 1
    && (int) $ejercicioId
        !== (int) $asignacionActual['ejercicio_id']
) {
    $_SESSION['mensaje_error'] =
        'No puedes seleccionar un ejercicio inactivo.';

    header('Location: ' . $rutaEdicion);
    exit;
}

/*
|--------------------------------------------------------------------------
| Preparar mediciones
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

/*
|--------------------------------------------------------------------------
| Limpiar mediciones que no correspondan al ejercicio
|--------------------------------------------------------------------------
*/

switch ($ejercicio['tipo_medicion']) {
    case 'tiempo':
        $series = null;
        $repeticiones = null;
        $rounds = null;
        $distancia = null;
        break;

    case 'rounds':
        $series = null;
        $repeticiones = null;
        $distancia = null;
        break;

    case 'series_repeticiones':
        $rounds = null;
        $duracion = null;
        $distancia = null;
        break;

    case 'distancia':
        $series = null;
        $repeticiones = null;
        $rounds = null;
        break;

    case 'libre':
        $series = null;
        $repeticiones = null;
        $rounds = null;
        $duracion = null;
        $distancia = null;
        break;
}

$diaAnterior = $asignacionActual['dia_semana'];

try {
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Volver a bloquear y comprobar la planeación
    |--------------------------------------------------------------------------
    */

    $bloqueo = $pdo->prepare(
        'SELECT
            pe.id,
            pe.planeacion_id,
            pe.dia_semana,
            pe.orden,
            p.estado AS planeacion_estado

        FROM planeacion_ejercicios AS pe

        INNER JOIN planeaciones AS p
            ON p.id = pe.planeacion_id

        WHERE pe.id = :id
        LIMIT 1
        FOR UPDATE'
    );

    $bloqueo->execute([
        'id' => $asignacionId,
    ]);

    $registroBloqueado = $bloqueo->fetch();

    if (!$registroBloqueado) {
        throw new RuntimeException(
            'La asignación dejó de existir.'
        );
    }

    if (!in_array(
        $registroBloqueado['planeacion_estado'],
        ['borrador', 'activa'],
        true
    )) {
        throw new RuntimeException(
            'La planeación ya no permite modificaciones.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Si cambia de día, colocarlo al final del nuevo día
    |--------------------------------------------------------------------------
    */

    $nuevoOrden = (int) $registroBloqueado['orden'];

    if ($diaAnterior !== $diaSemana) {
        $consultaOrden = $pdo->prepare(
            'SELECT COALESCE(MAX(orden), 0) + 1
            FROM planeacion_ejercicios
            WHERE planeacion_id = :planeacion_id
                AND dia_semana = :dia'
        );

        $consultaOrden->execute([
            'planeacion_id' => $planeacionId,
            'dia' => $diaSemana,
        ]);

        $nuevoOrden = (int) $consultaOrden->fetchColumn();
    }

    $actualizar = $pdo->prepare(
        'UPDATE planeacion_ejercicios
        SET
            ejercicio_id = :ejercicio_id,
            dia_semana = :dia_semana,
            orden = :orden,
            series = :series,
            repeticiones = :repeticiones,
            rounds = :rounds,
            duracion_minutos = :duracion_minutos,
            descanso_segundos = :descanso_segundos,
            distancia_metros = :distancia_metros,
            intensidad = :intensidad,
            indicaciones = :indicaciones
        WHERE id = :id'
    );

    $actualizar->execute([
        'ejercicio_id' => $ejercicioId,
        'dia_semana' => $diaSemana,
        'orden' => $nuevoOrden,
        'series' => $series,
        'repeticiones' => $repeticiones,
        'rounds' => $rounds,
        'duracion_minutos' => $duracion,
        'descanso_segundos' => $descanso,
        'distancia_metros' => $distancia,
        'intensidad' => $intensidad,
        'indicaciones' => $indicaciones,
        'id' => $asignacionId,
    ]);

    normalizarOrdenDia(
        $pdo,
        $planeacionId,
        $diaAnterior
    );

    if ($diaAnterior !== $diaSemana) {
        normalizarOrdenDia(
            $pdo,
            $planeacionId,
            $diaSemana
        );
    }

    $pdo->commit();

    unset($_SESSION['datos_edicion_planeacion_ejercicio']);

    $_SESSION['mensaje_exito'] =
        'El ejercicio asignado fue actualizado correctamente.';

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
        'Error al actualizar ejercicio asignado: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible actualizar el ejercicio asignado.';

    header('Location: ' . $rutaEdicion);
    exit;
}