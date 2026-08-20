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

$direccion = trim(
    (string) ($_POST['direccion'] ?? '')
);

if (
    !$asignacionId
    || $asignacionId < 1
    || !in_array($direccion, ['arriba', 'abajo'], true)
) {
    $_SESSION['mensaje_error'] =
        'No fue posible realizar el movimiento.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

function normalizarOrdenMovimiento(
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

try {
    $pdo->beginTransaction();

    $consultaActual = $pdo->prepare(
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

    $consultaActual->execute([
        'id' => $asignacionId,
    ]);

    $actual = $consultaActual->fetch();

    if (!$actual) {
        throw new RuntimeException(
            'El ejercicio asignado no existe.'
        );
    }

    $planeacionId = (int) $actual['planeacion_id'];
    $dia = $actual['dia_semana'];

    if (!in_array(
        $actual['planeacion_estado'],
        ['borrador', 'activa'],
        true
    )) {
        throw new RuntimeException(
            'La planeación no permite modificaciones.'
        );
    }

    normalizarOrdenMovimiento(
        $pdo,
        $planeacionId,
        $dia
    );

    /*
    |--------------------------------------------------------------------------
    | Recuperar nuevamente el orden normalizado
    |--------------------------------------------------------------------------
    */

    $consultaActual->execute([
        'id' => $asignacionId,
    ]);

    $actual = $consultaActual->fetch();

    if ($direccion === 'arriba') {
        $consultaVecino = $pdo->prepare(
            'SELECT id, orden
            FROM planeacion_ejercicios
            WHERE planeacion_id = :planeacion_id
                AND dia_semana = :dia
                AND orden < :orden
            ORDER BY orden DESC
            LIMIT 1
            FOR UPDATE'
        );
    } else {
        $consultaVecino = $pdo->prepare(
            'SELECT id, orden
            FROM planeacion_ejercicios
            WHERE planeacion_id = :planeacion_id
                AND dia_semana = :dia
                AND orden > :orden
            ORDER BY orden ASC
            LIMIT 1
            FOR UPDATE'
        );
    }

    $consultaVecino->execute([
        'planeacion_id' => $planeacionId,
        'dia' => $dia,
        'orden' => $actual['orden'],
    ]);

    $vecino = $consultaVecino->fetch();

    if (!$vecino) {
        $pdo->commit();

        $_SESSION['mensaje_error'] =
            $direccion === 'arriba'
            ? 'El ejercicio ya se encuentra en la primera posición.'
            : 'El ejercicio ya se encuentra en la última posición.';

        header(
            'Location: '
            . BASE_URL
            . '/planeaciones/ver.php?id='
            . $planeacionId
        );

        exit;
    }

    $actualizarOrden = $pdo->prepare(
        'UPDATE planeacion_ejercicios
        SET orden = :orden
        WHERE id = :id'
    );

    $actualizarOrden->execute([
        'orden' => $vecino['orden'],
        'id' => $actual['id'],
    ]);

    $actualizarOrden->execute([
        'orden' => $actual['orden'],
        'id' => $vecino['id'],
    ]);

    $pdo->commit();

    $_SESSION['mensaje_exito'] =
        'El orden del ejercicio fue actualizado.';

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
        'Error al mover ejercicio: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible cambiar el orden del ejercicio.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}