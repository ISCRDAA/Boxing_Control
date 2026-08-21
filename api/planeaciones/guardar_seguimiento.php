<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/listar.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

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

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/listar.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Datos principales
|--------------------------------------------------------------------------
*/

$planeacionId = filter_var(
    $_POST['planeacion_id'] ?? null,
    FILTER_VALIDATE_INT
);

$fecha = trim(
    (string) ($_POST['fecha'] ?? '')
);

$observacionesGenerales = trim(
    (string) (
        $_POST['observaciones_generales'] ?? ''
    )
);

if (!$planeacionId || $planeacionId < 1) {
    $_SESSION['mensaje_error'] =
        'La planeación seleccionada no es válida.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/listar.php'
    );

    exit;
}

$rutaSeguimiento =
    BASE_URL
    . '/planeaciones/seguimiento.php?planeacion_id='
    . $planeacionId
    . '&fecha='
    . urlencode($fecha);

function fechaGuardarSeguimientoValida(
    string $fecha
): bool {
    $objeto = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $objeto !== false
        && $objeto->format('Y-m-d') === $fecha;
}

if (!fechaGuardarSeguimientoValida($fecha)) {
    $_SESSION['mensaje_error'] =
        'La fecha del entrenamiento no es válida.';

    header('Location: ' . $rutaSeguimiento);
    exit;
}

if (mb_strlen($observacionesGenerales) > 1000) {
    $_SESSION['mensaje_error'] =
        'Las observaciones generales son demasiado largas.';

    header('Location: ' . $rutaSeguimiento);
    exit;
}

$estadosRecibidos =
    is_array($_POST['estado'] ?? null)
    ? $_POST['estado']
    : [];

$observacionesRecibidas =
    is_array($_POST['observacion'] ?? null)
    ? $_POST['observacion']
    : [];

$estadosPermitidos = [
    'pendiente',
    'completado',
    'parcial',
    'no_realizado',
];

$usuario = usuarioActual();

try {

    /*
    |--------------------------------------------------------------------------
    | Consultar planeación
    |--------------------------------------------------------------------------
    */

    $consultaPlaneacion = $pdo->prepare(
        'SELECT
            id,
            fecha_inicio,
            fecha_fin,
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
        $_SESSION['mensaje_error'] =
            'La planeación no existe.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/listar.php'
        );

        exit;
    }

    if ($planeacion['estado'] !== 'activa') {
        $_SESSION['mensaje_error'] =
            'La planeación ya no se encuentra activa.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/ver.php?id='
            . $planeacionId
        );

        exit;
    }

    if ($fecha < $planeacion['fecha_inicio']) {
        $_SESSION['mensaje_error'] =
            'La fecha es anterior al inicio de la planeación.';

        header('Location: ' . $rutaSeguimiento);
        exit;
    }

    if (
        $planeacion['fecha_fin']
        && $fecha > $planeacion['fecha_fin']
    ) {
        $_SESSION['mensaje_error'] =
            'La fecha es posterior al final de la planeación.';

        header('Location: ' . $rutaSeguimiento);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Determinar día
    |--------------------------------------------------------------------------
    */

    $numeroDia = (int) date(
        'N',
        strtotime($fecha)
    );

    $dias = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
        7 => 'domingo',
    ];

    $diaSemana =
        $dias[$numeroDia] ?? null;

    if ($diaSemana === null) {
        throw new RuntimeException(
            'No fue posible determinar el día.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener ejercicios válidos directamente de BD
    |--------------------------------------------------------------------------
    */

    $consultaEjercicios = $pdo->prepare(
        'SELECT id
        FROM planeacion_ejercicios
        WHERE planeacion_id = :planeacion_id
            AND dia_semana = :dia
        ORDER BY orden ASC'
    );

    $consultaEjercicios->execute([
        'planeacion_id' => $planeacionId,
        'dia' => $diaSemana,
    ]);

    $ejercicioIds =
        $consultaEjercicios->fetchAll(
            PDO::FETCH_COLUMN
        );

    if (empty($ejercicioIds)) {
        $_SESSION['mensaje_error'] =
            'No existen ejercicios asignados para ese día.';

        header('Location: ' . $rutaSeguimiento);
        exit;
    }

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Crear o actualizar sesión
    |--------------------------------------------------------------------------
    */

    $consultaSesion = $pdo->prepare(
        'SELECT id
        FROM sesiones_entrenamiento
        WHERE planeacion_id = :planeacion_id
            AND fecha = :fecha
        LIMIT 1
        FOR UPDATE'
    );

    $consultaSesion->execute([
        'planeacion_id' => $planeacionId,
        'fecha' => $fecha,
    ]);

    $sesionId =
        $consultaSesion->fetchColumn();

    $observacionesGenerales =
        $observacionesGenerales !== ''
        ? $observacionesGenerales
        : null;

    if ($sesionId) {

        $actualizarSesion = $pdo->prepare(
            'UPDATE sesiones_entrenamiento
            SET
                usuario_id = :usuario_id,
                observaciones = :observaciones
            WHERE id = :id'
        );

        $actualizarSesion->execute([
            'usuario_id' => $usuario['id'],
            'observaciones' =>
                $observacionesGenerales,
            'id' => $sesionId,
        ]);

        $sesionId = (int) $sesionId;

    } else {

        $insertarSesion = $pdo->prepare(
            'INSERT INTO sesiones_entrenamiento (
                planeacion_id,
                usuario_id,
                fecha,
                observaciones
            ) VALUES (
                :planeacion_id,
                :usuario_id,
                :fecha,
                :observaciones
            )'
        );

        $insertarSesion->execute([
            'planeacion_id' => $planeacionId,
            'usuario_id' => $usuario['id'],
            'fecha' => $fecha,
            'observaciones' =>
                $observacionesGenerales,
        ]);

        $sesionId =
            (int) $pdo->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Insertar / actualizar cada ejercicio
    |--------------------------------------------------------------------------
    */

    $guardarSeguimiento = $pdo->prepare(
        'INSERT INTO seguimiento_ejercicios (
            sesion_id,
            planeacion_ejercicio_id,
            estado,
            observacion
        ) VALUES (
            :sesion_id,
            :planeacion_ejercicio_id,
            :estado,
            :observacion
        )

        ON DUPLICATE KEY UPDATE
            estado = VALUES(estado),
            observacion = VALUES(observacion)'
    );

    foreach ($ejercicioIds as $ejercicioId) {

        $ejercicioId = (int) $ejercicioId;

        $estado = trim(
            (string) (
                $estadosRecibidos[
                    $ejercicioId
                ] ?? 'pendiente'
            )
        );

        if (!in_array(
            $estado,
            $estadosPermitidos,
            true
        )) {
            $estado = 'pendiente';
        }

        $observacion = trim(
            (string) (
                $observacionesRecibidas[
                    $ejercicioId
                ] ?? ''
            )
        );

        if (mb_strlen($observacion) > 500) {
            throw new RuntimeException(
                'Una observación supera '
                . 'los 500 caracteres.'
            );
        }

        $observacion =
            $observacion !== ''
            ? $observacion
            : null;

        $guardarSeguimiento->execute([
            'sesion_id' => $sesionId,
            'planeacion_ejercicio_id' =>
                $ejercicioId,
            'estado' => $estado,
            'observacion' => $observacion,
        ]);
    }

    $pdo->commit();

    $_SESSION['mensaje_exito'] =
        'El seguimiento del entrenamiento '
        . 'fue guardado correctamente.';

    header('Location: ' . $rutaSeguimiento);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Error al guardar seguimiento: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible guardar el seguimiento.';

    header('Location: ' . $rutaSeguimiento);
    exit;
}