<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/alumnos/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar token CSRF
|--------------------------------------------------------------------------
*/

$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    $_SESSION['mensaje_error'] =
        'La solicitud no es válida. Intenta nuevamente.';

    header('Location: ' . BASE_URL . '/alumnos/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Conservar los datos si ocurre un error
|--------------------------------------------------------------------------
*/

$_SESSION['datos_formulario'] = $_POST;

unset($_SESSION['datos_formulario']['csrf_token']);

/*
|--------------------------------------------------------------------------
| Recibir los datos
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Función para validar fechas
|--------------------------------------------------------------------------
*/

function fechaValida(string $fecha): bool
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

if ($nombres === '') {
    $errores[] = 'Debes escribir los nombres del alumno.';
}

if ($apellidos === '') {
    $errores[] = 'Debes escribir los apellidos del alumno.';
}

if ($fechaIngreso === '') {
    $errores[] = 'Debes seleccionar la fecha de ingreso.';
}

if (!fechaValida($fechaNacimiento)) {
    $errores[] = 'La fecha de nacimiento no es válida.';
}

if (!fechaValida($fechaIngreso)) {
    $errores[] = 'La fecha de ingreso no es válida.';
}

if (!fechaValida($proximoPago)) {
    $errores[] = 'La próxima fecha de pago no es válida.';
}

$tiposPagoPermitidos = [
    'semanal',
    'mensual',
];

if (!in_array($tipoPago, $tiposPagoPermitidos, true)) {
    $errores[] = 'El tipo de pago seleccionado no es válido.';
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
    $cuotaTexto === '' ||
    !is_numeric($cuotaTexto) ||
    (float) $cuotaTexto < 0
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

    header('Location: ' . BASE_URL . '/alumnos/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Preparar valores opcionales
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
    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Insertar al alumno
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO alumnos (
            numero_alumno,
            nombres,
            apellidos,
            fecha_nacimiento,
            telefono,
            contacto_emergencia,
            telefono_emergencia,
            fecha_ingreso,
            tipo_pago,
            cuota,
            proximo_pago,
            nivel,
            objetivo,
            observaciones,
            estado
        ) VALUES (
            NULL,
            :nombres,
            :apellidos,
            :fecha_nacimiento,
            :telefono,
            :contacto_emergencia,
            :telefono_emergencia,
            :fecha_ingreso,
            :tipo_pago,
            :cuota,
            :proximo_pago,
            :nivel,
            :objetivo,
            :observaciones,
            "activo"
        )'
    );

    $insertar->execute([
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
    ]);

    $alumnoId = (int) $pdo->lastInsertId();

    /*
    |--------------------------------------------------------------------------
    | Crear número automático de alumno
    |--------------------------------------------------------------------------
    |
    | Ejemplo:
    | BOX-00001
    | BOX-00002
    |
    */

    $numeroAlumno = 'BOX-' . str_pad(
        (string) $alumnoId,
        5,
        '0',
        STR_PAD_LEFT
    );

    $actualizarNumero = $pdo->prepare(
        'UPDATE alumnos
         SET numero_alumno = :numero_alumno
         WHERE id = :id'
    );

    $actualizarNumero->execute([
        'numero_alumno' => $numeroAlumno,
        'id' => $alumnoId,
    ]);

    $pdo->commit();

    unset($_SESSION['datos_formulario']);

    $_SESSION['mensaje_exito'] =
        'El alumno fue registrado correctamente con el número '
        . $numeroAlumno
        . '.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Error al registrar alumno: ' . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible registrar al alumno. Intenta nuevamente.';

    header('Location: ' . BASE_URL . '/alumnos/crear.php');
    exit;
}