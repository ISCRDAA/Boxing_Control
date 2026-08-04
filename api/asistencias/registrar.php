<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/asistencias/listar.php');
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

    header('Location: ' . BASE_URL . '/asistencias/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$alumnoId = filter_var(
    $_POST['alumno_id'] ?? null,
    FILTER_VALIDATE_INT
);

$origen = (string) ($_POST['origen'] ?? 'listar');

if (!$alumnoId || $alumnoId < 1) {
    $_SESSION['mensaje_error'] =
        'El alumno seleccionado no es válido.';

    header('Location: ' . BASE_URL . '/asistencias/listar.php');
    exit;
}

$rutaRegreso = $origen === 'perfil'
    ? BASE_URL . '/alumnos/ver.php?id=' . $alumnoId
    : BASE_URL . '/asistencias/listar.php';

$usuario = usuarioActual();

$fechaHoy = date('Y-m-d');
$horaActual = date('H:i:s');

try {
    /*
    |--------------------------------------------------------------------------
    | Verificar alumno
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
            'El alumno solicitado no existe.';

        header('Location: ' . BASE_URL . '/asistencias/listar.php');
        exit;
    }

    if ($alumno['estado'] !== 'activo') {
        $_SESSION['mensaje_error'] =
            'No puedes registrar asistencia a un alumno inactivo.';

        header('Location: ' . $rutaRegreso);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar asistencia
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO asistencias (
            alumno_id,
            usuario_id,
            fecha,
            hora_llegada,
            observaciones
        ) VALUES (
            :alumno_id,
            :usuario_id,
            :fecha,
            :hora_llegada,
            NULL
        )'
    );

    $insertar->execute([
        'alumno_id' => $alumnoId,
        'usuario_id' => $usuario['id'],
        'fecha' => $fechaHoy,
        'hora_llegada' => $horaActual,
    ]);

    $_SESSION['mensaje_exito'] =
        'Asistencia registrada para '
        . $alumno['nombres']
        . ' '
        . $alumno['apellidos']
        . ' a las '
        . date('H:i', strtotime($horaActual))
        . ' horas.';

    header('Location: ' . $rutaRegreso);
    exit;
} catch (PDOException $e) {
    $codigoMySQL = $e->errorInfo[1] ?? null;

    if ((int) $codigoMySQL === 1062) {
        $_SESSION['mensaje_error'] =
            'La asistencia de este alumno ya fue registrada hoy.';

        header('Location: ' . $rutaRegreso);
        exit;
    }

    error_log(
        'Error al registrar asistencia: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible registrar la asistencia.';

    header('Location: ' . $rutaRegreso);
    exit;
}