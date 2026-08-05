<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

/*
|--------------------------------------------------------------------------
| Validar ejercicio
|--------------------------------------------------------------------------
*/

$ejercicioId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$ejercicioId || $ejercicioId < 1) {
    $_SESSION['mensaje_error'] =
        'El ejercicio seleccionado no es válido.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar ejercicio
|--------------------------------------------------------------------------
*/

$consulta = $pdo->prepare(
    'SELECT
        id,
        nombre,
        categoria,
        tipo_medicion,
        descripcion,
        activo
    FROM ejercicios
    WHERE id = :id
    LIMIT 1'
);

$consulta->execute([
    'id' => $ejercicioId,
]);

$ejercicio = $consulta->fetch();

if (!$ejercicio) {
    $_SESSION['mensaje_error'] =
        'El ejercicio solicitado no existe.';

    header('Location: ' . BASE_URL . '/ejercicios/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Recuperar datos anteriores
|--------------------------------------------------------------------------
*/

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosEdicion = $_SESSION['datos_edicion_ejercicio'] ?? null;

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_edicion_ejercicio']
);

if (
    is_array($datosEdicion)
    && isset($datosEdicion['id'])
    && (int) $datosEdicion['id'] === $ejercicioId
) {
    $datos = array_merge($ejercicio, $datosEdicion);
} else {
    $datos = $ejercicio;
}

/*
|--------------------------------------------------------------------------
| Opciones
|--------------------------------------------------------------------------
*/

$categorias = [
    'calentamiento' => 'Calentamiento',
    'cardio' => 'Cardio',
    'tecnica' => 'Técnica',
    'fuerza' => 'Fuerza',
    'costal' => 'Costal',
    'sombra' => 'Sombra',
    'manoplas' => 'Manoplas',
    'sparring' => 'Sparring',
    'abdomen' => 'Abdomen',
    'pierna' => 'Pierna',
    'otro' => 'Otro',
];

$tiposMedicion = [
    'tiempo' => 'Tiempo',
    'rounds' => 'Rounds',
    'series_repeticiones' => 'Series y repeticiones',
    'distancia' => 'Distancia',
    'libre' => 'Medición libre',
];

function valorEdicionEjercicio(
    array $datos,
    string $campo
): string {
    return htmlspecialchars(
        (string) ($datos[$campo] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar ejercicio | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/ejercicios.css"
    >
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Edición de ejercicio</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/ejercicios/listar.php"
        >
            Volver a ejercicios
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">

                <div>
                    <h2>Editar ejercicio</h2>

                    <p>
                        Modifica la información del ejercicio seleccionado.
                    </p>
                </div>

                <?php if ((int) $ejercicio['activo'] === 1): ?>

                    <span class="badge badge-success">
                        Activo
                    </span>

                <?php else: ?>

                    <span class="badge badge-danger">
                        Inactivo
                    </span>

                <?php endif; ?>

            </div>

            <?php if ($mensajeError): ?>

                <div class="alert alert-error">
                    <?= htmlspecialchars(
                        $mensajeError,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <form
                action="<?= BASE_URL ?>/api/ejercicios/actualizar.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= $ejercicioId ?>"
                >

                <div class="form-grid">

                    <div class="form-group form-group-full">

                        <label for="nombre">
                            Nombre del ejercicio *
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="nombre"
                            name="nombre"
                            maxlength="120"
                            value="<?= valorEdicionEjercicio(
                                $datos,
                                'nombre'
                            ) ?>"
                            required
                            autofocus
                        >

                    </div>

                    <div class="form-group">

                        <label for="categoria">
                            Categoría *
                        </label>

                        <select
                            class="form-control"
                            id="categoria"
                            name="categoria"
                            required
                        >

                            <?php foreach (
                                $categorias as $valor => $texto
                            ): ?>

                                <option
                                    value="<?= $valor ?>"
                                    <?= $datos['categoria'] === $valor
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $texto ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="tipo_medicion">
                            Forma principal de medición *
                        </label>

                        <select
                            class="form-control"
                            id="tipo_medicion"
                            name="tipo_medicion"
                            required
                        >

                            <?php foreach (
                                $tiposMedicion as $valor => $texto
                            ): ?>

                                <option
                                    value="<?= $valor ?>"
                                    <?= $datos['tipo_medicion'] === $valor
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $texto ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group form-group-full">

                        <label for="descripcion">
                            Descripción o indicaciones
                        </label>

                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            maxlength="500"
                        ><?= valorEdicionEjercicio(
                            $datos,
                            'descripcion'
                        ) ?></textarea>

                        <small class="field-help">
                            Máximo 500 caracteres.
                        </small>

                    </div>

                </div>

                <div class="form-actions">

                    <a
                        class="btn-secondary"
                        href="<?= BASE_URL ?>/ejercicios/listar.php"
                    >
                        Cancelar
                    </a>

                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Guardar cambios
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>