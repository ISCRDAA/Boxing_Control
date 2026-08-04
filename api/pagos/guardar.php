<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pagos/crear.php');
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

    header('Location: ' . BASE_URL . '/pagos/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Conservar datos ante un error
|--------------------------------------------------------------------------
*/

$_SESSION['datos_pago'] = $_POST;

unset($_SESSION['datos_pago']['csrf_token']);

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$alumnoId = filter_var(
    $_POST['alumno_id'] ?? null,
    FILTER_VALIDATE_INT
);

$concepto = trim(
    (string) ($_POST['concepto'] ?? '')
);

$montoTexto = trim(
    (string) ($_POST['monto'] ?? '')
);

$fechaPago = trim(
    (string) ($_POST['fecha_pago'] ?? '')
);

$metodoPago = trim(
    (string) ($_POST['metodo_pago'] ?? '')
);

$proximoPago = trim(
    (string) ($_POST['proximo_pago'] ?? '')
);

$observaciones = trim(
    (string) ($_POST['observaciones'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Validar fechas
|--------------------------------------------------------------------------
*/

function fechaPagoValida(string $fecha): bool
{
    if ($fecha === '') {
        return false;
    }

    $fechaConvertida = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $fechaConvertida !== false
        && $fechaConvertida->format('Y-m-d') === $fecha;
}

function fechaOpcionalValida(string $fecha): bool
{
    if ($fecha === '') {
        return true;
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

if ($concepto === '') {
    $errores[] = 'Debes escribir el concepto del pago.';
}

if (mb_strlen($concepto) > 100) {
    $errores[] = 'El concepto es demasiado largo.';
}

if (
    $montoTexto === ''
    || !is_numeric($montoTexto)
    || (float) $montoTexto <= 0
) {
    $errores[] =
        'La cantidad debe ser mayor que cero.';
}

if (!fechaPagoValida($fechaPago)) {
    $errores[] =
        'La fecha del pago no es válida.';
}

if (!fechaOpcionalValida($proximoPago)) {
    $errores[] =
        'La próxima fecha de pago no es válida.';
}

if (
    $proximoPago !== ''
    && fechaPagoValida($fechaPago)
    && $proximoPago <= $fechaPago
) {
    $errores[] =
        'La próxima fecha debe ser posterior a la fecha del pago.';
}

$metodosPermitidos = [
    'efectivo',
    'transferencia',
];

if (!in_array(
    $metodoPago,
    $metodosPermitidos,
    true
)) {
    $errores[] =
        'El método de pago no es válido.';
}

if (mb_strlen($observaciones) > 500) {
    $errores[] =
        'Las observaciones son demasiado largas.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . BASE_URL . '/pagos/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Preparar valores
|--------------------------------------------------------------------------
*/

$monto = number_format(
    (float) $montoTexto,
    2,
    '.',
    ''
);

$proximoPago = $proximoPago !== ''
    ? $proximoPago
    : null;

$observaciones = $observaciones !== ''
    ? $observaciones
    : null;

$usuario = usuarioActual();

try {
    /*
    |--------------------------------------------------------------------------
    | Comprobar que el alumno exista y esté activo
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

        header('Location: ' . BASE_URL . '/pagos/crear.php');
        exit;
    }

    if ($alumno['estado'] !== 'activo') {
        $_SESSION['mensaje_error'] =
            'No puedes registrar un pago a un alumno inactivo.';

        header('Location: ' . BASE_URL . '/pagos/crear.php');
        exit;
    }

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Registrar pago
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO pagos (
            alumno_id,
            usuario_id,
            concepto,
            monto,
            fecha_pago,
            metodo_pago,
            proximo_pago,
            observaciones
        ) VALUES (
            :alumno_id,
            :usuario_id,
            :concepto,
            :monto,
            :fecha_pago,
            :metodo_pago,
            :proximo_pago,
            :observaciones
        )'
    );

    $insertar->execute([
        'alumno_id' => $alumnoId,
        'usuario_id' => $usuario['id'],
        'concepto' => $concepto,
        'monto' => $monto,
        'fecha_pago' => $fechaPago,
        'metodo_pago' => $metodoPago,
        'proximo_pago' => $proximoPago,
        'observaciones' => $observaciones,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Actualizar vencimiento del alumno
    |--------------------------------------------------------------------------
    */

    if ($proximoPago !== null) {
        $actualizarAlumno = $pdo->prepare(
            'UPDATE alumnos
             SET proximo_pago = :proximo_pago
             WHERE id = :id'
        );

        $actualizarAlumno->execute([
            'proximo_pago' => $proximoPago,
            'id' => $alumnoId,
        ]);
    }

    $pdo->commit();

    unset($_SESSION['datos_pago']);

    $_SESSION['mensaje_exito'] =
        'El pago de '
        . $alumno['nombres']
        . ' '
        . $alumno['apellidos']
        . ' fue registrado correctamente.';

    header('Location: ' . BASE_URL . '/pagos/listar.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Error al registrar pago: ' . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible registrar el pago.';

    header('Location: ' . BASE_URL . '/pagos/crear.php');
    exit;
}