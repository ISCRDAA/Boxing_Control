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

$planeacionId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$nuevoEstado = trim(
    (string) ($_POST['estado'] ?? '')
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

$rutaPlaneacion =
    BASE_URL
    . '/planeaciones/ver.php?id='
    . $planeacionId;

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Bloquear la planeación
    |--------------------------------------------------------------------------
    */

    $consulta = $pdo->prepare(
        'SELECT
            id,
            nombre,
            estado
        FROM planeaciones
        WHERE id = :id
        LIMIT 1
        FOR UPDATE'
    );

    $consulta->execute([
        'id' => $planeacionId,
    ]);

    $planeacion = $consulta->fetch();

    if (!$planeacion) {
        throw new RuntimeException(
            'La planeación no existe.'
        );
    }

    $estadoActual =
        $planeacion['estado'];

    /*
    |--------------------------------------------------------------------------
    | Transiciones permitidas
    |--------------------------------------------------------------------------
    */

    $transicionesPermitidas = [

        'borrador' => [
            'activa',
            'cancelada',
        ],

        'activa' => [
            'terminada',
            'cancelada',
        ],

        'terminada' => [],

        'cancelada' => [],
    ];

    if (
        !isset(
            $transicionesPermitidas[$estadoActual]
        )
        || !in_array(
            $nuevoEstado,
            $transicionesPermitidas[$estadoActual],
            true
        )
    ) {

        throw new RuntimeException(
            'El cambio de estado solicitado no está permitido.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Para activar, debe existir al menos un ejercicio
    |--------------------------------------------------------------------------
    */

    if ($nuevoEstado === 'activa') {

        $consultaEjercicios = $pdo->prepare(
            'SELECT COUNT(*)
            FROM planeacion_ejercicios
            WHERE planeacion_id = :planeacion_id'
        );

        $consultaEjercicios->execute([
            'planeacion_id' => $planeacionId,
        ]);

        $cantidadEjercicios =
            (int) $consultaEjercicios->fetchColumn();

        if ($cantidadEjercicios === 0) {

            $pdo->rollBack();

            $_SESSION['mensaje_error'] =
                'No puedes activar una planeación '
                . 'que no contiene ejercicios.';

            header(
                'Location: '
                . $rutaPlaneacion
            );

            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar estado
    |--------------------------------------------------------------------------
    */

    $actualizar = $pdo->prepare(
        'UPDATE planeaciones
        SET estado = :estado
        WHERE id = :id'
    );

    $actualizar->execute([
        'estado' => $nuevoEstado,
        'id' => $planeacionId,
    ]);

    $pdo->commit();

    $mensajes = [

        'activa' =>
            'La planeación fue activada correctamente.',

        'terminada' =>
            'La planeación fue marcada como terminada.',

        'cancelada' =>
            'La planeación fue cancelada.',
    ];

    $_SESSION['mensaje_exito'] =
        $mensajes[$nuevoEstado]
        ?? 'El estado fue actualizado.';

    header(
        'Location: '
        . $rutaPlaneacion
    );

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Error al cambiar estado de planeación: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible cambiar el estado de la planeación.';

    header(
        'Location: '
        . $rutaPlaneacion
    );

    exit;
}