<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/session.php';

requerirSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
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

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Validar ID
|--------------------------------------------------------------------------
*/

$ejercicioId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$ejercicioId || $ejercicioId < 1) {
    $_SESSION['mensaje_error'] =
        'No fue posible identificar el ejercicio.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Conservar información en caso de error
|--------------------------------------------------------------------------
*/

$_SESSION['datos_edicion_ejercicio'] = $_POST;

unset(
    $_SESSION['datos_edicion_ejercicio']['csrf_token']
);

/*
|--------------------------------------------------------------------------
| Recibir información
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

    header(
        'Location: '
        . BASE_URL
        . '/ejercicios/editar.php?id='
        . $ejercicioId
    );

    exit;
}

$descripcion = $descripcion !== ''
    ? $descripcion
    : null;

try {
    /*
    |--------------------------------------------------------------------------
    | Comprobar existencia
    |--------------------------------------------------------------------------
    */

    $consultaEjercicio = $pdo->prepare(
        'SELECT id
        FROM ejercicios
        WHERE id = :id
        LIMIT 1'
    );

    $consultaEjercicio->execute([
        'id' => $ejercicioId,
    ]);

    if (!$consultaEjercicio->fetch()) {
        unset($_SESSION['datos_edicion_ejercicio']);

        $_SESSION['mensaje_error'] =
            'El ejercicio que intentas modificar no existe.';

        header('Location: ' . BASE_URL . '/ejercicios/listar.php');
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Evitar nombres duplicados
    |--------------------------------------------------------------------------
    */

    $verificarNombre = $pdo->prepare(
        'SELECT id
        FROM ejercicios
        WHERE nombre = :nombre
            AND id <> :id
        LIMIT 1'
    );

    $verificarNombre->execute([
        'nombre' => $nombre,
        'id' => $ejercicioId,
    ]);

    if ($verificarNombre->fetch()) {
        $_SESSION['mensaje_error'] =
            'Ya existe otro ejercicio registrado con ese nombre.';

        header(
            'Location: '
            . BASE_URL
            . '/ejercicios/editar.php?id='
            . $ejercicioId
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    $actualizar = $pdo->prepare(
        'UPDATE ejercicios
        SET
            nombre = :nombre,
            categoria = :categoria,
            tipo_medicion = :tipo_medicion,
            descripcion = :descripcion
        WHERE id = :id'
    );

    $actualizar->execute([
        'nombre' => $nombre,
        'categoria' => $categoria,
        'tipo_medicion' => $tipoMedicion,
        'descripcion' => $descripcion,
        'id' => $ejercicioId,
    ]);

    unset($_SESSION['datos_edicion_ejercicio']);

    $_SESSION['mensaje_exito'] =
        'El ejercicio fue actualizado correctamente.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
} catch (PDOException $e) {
    $codigoMySQL = (int) ($e->errorInfo[1] ?? 0);

    if ($codigoMySQL === 1062) {
        $_SESSION['mensaje_error'] =
            'Ya existe otro ejercicio con ese nombre.';

        header(
            'Location: '
            . BASE_URL
            . '/ejercicios/editar.php?id='
            . $ejercicioId
        );

        exit;
    }

    error_log(
        'Error al actualizar ejercicio: '
        . $e->getMessage()
    );

    $_SESSION['mensaje_error'] =
        'No fue posible actualizar el ejercicio.';

    header(
        'Location: '
        . BASE_URL
        . '/ejercicios/editar.php?id='
        . $ejercicioId
    );

    exit;
}