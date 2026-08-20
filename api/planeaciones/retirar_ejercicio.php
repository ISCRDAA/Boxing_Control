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
        'El ejercicio asignado no es válido.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

function normalizarOrdenRetiro(
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

    $consulta = $pdo->prepare(
        'SELECT
            pe.id,
            pe.planeacion_id,
            pe.dia_semana,
            e.nombre AS ejercicio_nombre,
            p.estado AS planeacion_estado

        FROM planeacion_ejercicios AS pe

        INNER JOIN ejercicios AS e
            ON e.id = pe.ejercicio_id

        INNER JOIN planeaciones AS p
            ON p.id = pe.planeacion_id

        WHERE pe.id = :id
        LIMIT 1
        FOR UPDATE'
    );

    $consulta->execute([
        'id' => $asignacionId,
    ]);

    $asignacion = $consulta->fetch();

    if (!$asignacion) {
        throw new RuntimeException(
            'El ejercicio asignado no existe.'
        );
    }

    $planeacionId = (int) $asignacion['planeacion_id'];
    $dia = $asignacion['dia_semana'];

    if (!in_array(
        $asignacion['planeacion_estado'],
        ['borrador', 'activa'],
        true
    )) {
        throw new RuntimeException(
            'La planeación no permite modificaciones.'
        );
    }

    $eliminar = $pdo->prepare(
        'DELETE FROM planeacion_ejercicios
        WHERE id = :id'
    );

    $eliminar->execute([
        'id' => $asignacionId,
    ]);

    normalizarOrdenRetiro(
        $pdo,
        $planeacionId,
        $dia
    );

    $pdo->commit();

    $_SESSION['mensaje_exito'] =
        'El ejercicio "'
        . $asignacion['ejercicio_nombre']
        . '" fue retirado de la planeación.';

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
        'Error al retirar ejercicio: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible retirar el ejercicio.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}