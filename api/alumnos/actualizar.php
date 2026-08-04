<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken)
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida. Intenta nuevamente.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

$alumnoId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$alumnoId || $alumnoId < 1) {
    $_SESSION['mensaje_error'] =
        'No fue posible identificar al alumno.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

$_SESSION['datos_edicion'] = $_POST;

unset($_SESSION['datos_edicion']['csrf_token']);

$nombres = trim((string) ($_POST['nombres'] ?? ''));
$apellidos = trim((string) ($_POST['apellidos'] ?? ''));

$fechaNacimiento = trim(
    (string) ($_POST['fecha_nacimiento'] ?? '')
);

$telefono = trim((string) ($_POST['telefono'] ?? ''));

$contactoEmergencia = trim(
    (string) ($_POST['contacto_emergencia'] ?? '')
);

$telefonoEmergencia = trim(
    (string) ($_POST['telefono_emergencia'] ?? '')
);

$fechaIngreso = trim(
    (string) ($_POST['fecha_ingreso'] ?? '')
);

$tipoPago = trim((string) ($_POST['tipo_pago'] ?? ''));
$cuotaTexto = trim((string) ($_POST['cuota'] ?? ''));

$proximoPago = trim(
    (string) ($_POST['proximo_pago'] ?? '')
);

$nivel = trim((string) ($_POST['nivel'] ?? ''));
$objetivo = trim((string) ($_POST['objetivo'] ?? ''));

$observaciones = trim(
    (string) ($_POST['observaciones'] ?? '')
);

function fechaValidaEdicion(string $fecha): bool
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

$errores = [];

if ($nombres === '') {
    $errores[] = 'Debes escribir los nombres.';
}

if ($apellidos === '') {
    $errores[] = 'Debes escribir los apellidos.';
}

if ($fechaIngreso === '') {
    $errores[] = 'Debes seleccionar la fecha de ingreso.';
}

if (!fechaValidaEdicion($fechaNacimiento)) {
    $errores[] = 'La fecha de nacimiento no es válida.';
}

if (!fechaValidaEdicion($fechaIngreso)) {
    $errores[] = 'La fecha de ingreso no es válida.';
}

if (!fechaValidaEdicion($proximoPago)) {
    $errores[] = 'La próxima fecha de pago no es válida.';
}

$tiposPagoPermitidos = [
    'semanal',
    'mensual',
];

if (!in_array($tipoPago, $tiposPagoPermitidos, true)) {
    $errores[] = 'El tipo de pago no es válido.';
}

$nivelesPermitidos = [
    'principiante',
    'intermedio',
    'avanzado',
    'competidor',
];

if (!in_array($nivel, $nivelesPermitidos, true)) {
    $errores[] = 'El nivel seleccionado no es válido.';
}

if (
    $cuotaTexto === ''
    || !is_numeric($cuotaTexto)
    || (float) $cuotaTexto < 0
) {
    $errores[] = 'La cuota debe ser una cantidad válida.';
}

if (mb_strlen($nombres) > 100) {
    $errores[] = 'Los nombres son demasiado largos.';
}

if (mb_strlen($apellidos) > 120) {
    $errores[] = 'Los apellidos son demasiado largos.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] = implode(' ', $errores);

    header(
        'Location: '
        . BASE_URL
        . '/alumnos/editar.php?id='
        . $alumnoId
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Convertir campos vacíos en NULL
|--------------------------------------------------------------------------
*/

$fechaNacimiento = $fechaNacimiento !== ''
    ? $fechaNacimiento
    : null;

$telefono = $telefono !== ''
    ? $telefono
    : null;

$contactoEmergencia = $contactoEmergencia !== ''
    ? $contactoEmergencia
    : null;

$telefonoEmergencia = $telefonoEmergencia !== ''
    ? $telefonoEmergencia
    : null;

$proximoPago = $proximoPago !== ''
    ? $proximoPago
    : null;

$objetivo = $objetivo !== ''
    ? $objetivo
    : null;

$observaciones = $observaciones !== ''
    ? $observaciones
    : null;

$cuota = number_format(
    (float) $cuotaTexto,
    2,
    '.',
    ''
);

try {
    $verificar = $pdo->prepare(
        'SELECT id
         FROM alumnos
         WHERE id = :id
         LIMIT 1'
    );

    $verificar->execute([
        'id' => $alumnoId,
    ]);

    if (!$verificar->fetch()) {
        unset($_SESSION['datos_edicion']);

        $_SESSION['mensaje_error'] =
            'El alumno que intentas modificar no existe.';

        header('Location: ' . BASE_URL . '/alumnos/listar.php');
        exit;
    }

    $actualizar = $pdo->prepare(
        'UPDATE alumnos
         SET
            nombres = :nombres,
            apellidos = :apellidos,
            fecha_nacimiento = :fecha_nacimiento,
            telefono = :telefono,
            contacto_emergencia = :contacto_emergencia,
            telefono_emergencia = :telefono_emergencia,
            fecha_ingreso = :fecha_ingreso,
            tipo_pago = :tipo_pago,
            cuota = :cuota,
            proximo_pago = :proximo_pago,
            nivel = :nivel,
            objetivo = :objetivo,
            observaciones = :observaciones
         WHERE id = :id'
    );

    $actualizar->execute([
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'fecha_nacimiento' => $fechaNacimiento,
        'telefono' => $telefono,
        'contacto_emergencia' => $contactoEmergencia,
        'telefono_emergencia' => $telefonoEmergencia,
        'fecha_ingreso' => $fechaIngreso,
        'tipo_pago' => $tipoPago,
        'cuota' => $cuota,
        'proximo_pago' => $proximoPago,
        'nivel' => $nivel,
        'objetivo' => $objetivo,
        'observaciones' => $observaciones,
        'id' => $alumnoId,
    ]);

    unset($_SESSION['datos_edicion']);

    $_SESSION['mensaje_exito'] =
        'Los datos del alumno fueron actualizados correctamente.';

    header(
        'Location: '
        . BASE_URL
        . '/alumnos/ver.php?id='
        . $alumnoId
    );

    exit;
} catch (PDOException $e) {
    error_log(
        'Error al actualizar alumno: ' . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible actualizar al alumno.';

    header(
        'Location: '
        . BASE_URL
        . '/alumnos/editar.php?id='
        . $alumnoId
    );

    exit;
}