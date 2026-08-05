<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/ejercicios/crear.php');
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

    header('Location: ' . BASE_URL . '/ejercicios/crear.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Conservar información si ocurre un error
|--------------------------------------------------------------------------
*/

$_SESSION['datos_ejercicio'] = $_POST;

unset($_SESSION['datos_ejercicio']['csrf_token']);

/*
|--------------------------------------------------------------------------
| Recibir datos
|--------------------------------------------------------------------------
*/

$nombre = trim(
    (string) ($_POST['nombre'] ?? '')
);

$categoria = trim(
    (string) ($_POST['categoria'] ?? '')
);

$tipoMedicion = trim(
    (string) ($_POST['tipo_medicion'] ?? '')
);

$descripcion = trim(
    (string) ($_POST['descripcion'] ?? '')
);

/*
|--------------------------------------------------------------------------
| Valores permitidos
|--------------------------------------------------------------------------
*/

$categoriasPermitidas = [
    'calentamiento',
    'cardio',
    'tecnica',
    'fuerza',
    'costal',
    'sombra',
    'manoplas',
    'sparring',
    'abdomen',
    'pierna',
    'otro',
];

$medicionesPermitidas = [
    'tiempo',
    'rounds',
    'series_repeticiones',
    'distancia',
    'libre',
];

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/

$errores = [];

if ($nombre === '') {
    $errores[] =
        'Debes escribir el nombre del ejercicio.';
}

if (mb_strlen($nombre) > 120) {
    $errores[] =
        'El nombre del ejercicio es demasiado largo.';
}

if (!in_array(
    $categoria,
    $categoriasPermitidas,
    true
)) {
    $errores[] =
        'La categoría seleccionada no es válida.';
}

if (!in_array(
    $tipoMedicion,
    $medicionesPermitidas,
    true
)) {
    $errores[] =
        'La forma de medición no es válida.';
}

if (mb_strlen($descripcion) > 500) {
    $errores[] =
        'La descripción no puede superar 500 caracteres.';
}

if (!empty($errores)) {
    $_SESSION['mensaje_error'] =
        implode(' ', $errores);

    header('Location: ' . BASE_URL . '/ejercicios/crear.php');
    exit;
}

$descripcion = $descripcion !== ''
    ? $descripcion
    : null;

$usuario = usuarioActual();

try {
    /*
    |--------------------------------------------------------------------------
    | Comprobar que no exista el mismo nombre
    |--------------------------------------------------------------------------
    */

    $verificar = $pdo->prepare(
        'SELECT id
         FROM ejercicios
         WHERE nombre = :nombre
         LIMIT 1'
    );

    $verificar->execute([
        'nombre' => $nombre,
    ]);

    if ($verificar->fetch()) {
        $_SESSION['mensaje_error'] =
            'Ya existe un ejercicio registrado con ese nombre.';

        header('Location: ' . BASE_URL . '/ejercicios/crear.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar ejercicio
    |--------------------------------------------------------------------------
    */

    $insertar = $pdo->prepare(
        'INSERT INTO ejercicios (
            nombre,
            categoria,
            tipo_medicion,
            descripcion,
            activo,
            creado_por
        ) VALUES (
            :nombre,
            :categoria,
            :tipo_medicion,
            :descripcion,
            1,
            :creado_por
        )'
    );

    $insertar->execute([
        'nombre' => $nombre,
        'categoria' => $categoria,
        'tipo_medicion' => $tipoMedicion,
        'descripcion' => $descripcion,
        'creado_por' => $usuario['id'],
    ]);

    unset($_SESSION['datos_ejercicio']);

    $_SESSION['mensaje_exito'] =
        'El ejercicio fue registrado correctamente.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
} catch (PDOException $e) {
    $codigoMySQL = (int) ($e->errorInfo[1] ?? 0);

    if ($codigoMySQL === 1062) {
        $_SESSION['mensaje_error'] =
            'Ya existe un ejercicio registrado con ese nombre.';

        header('Location: ' . BASE_URL . '/ejercicios/crear.php');
        exit;
    }

    error_log(
        'Error al registrar ejercicio: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible registrar el ejercicio.';

    header('Location: ' . BASE_URL . '/ejercicios/crear.php');
    exit;
}