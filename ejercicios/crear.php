<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

requerirSesion();

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_ejercicio'] ?? [];

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_ejercicio']
);

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

function valorEjercicio(
    array $datos,
    string $campo,
    string $predeterminado = ''
): string {
    return htmlspecialchars(
        (string) ($datos[$campo] ?? $predeterminado),
        ENT_QUOTES,
        'UTF-8'
    );
}

$categoriaSeleccionada =
    $datosAnteriores['categoria'] ?? 'calentamiento';

$medicionSeleccionada =
    $datosAnteriores['tipo_medicion'] ?? 'tiempo';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Registrar ejercicio | Gym Box</title>

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
            <p>Catálogo de ejercicios</p>
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
                    <h2>Registrar ejercicio</h2>

                    <p>
                        Agrega un ejercicio para utilizarlo posteriormente
                        en las planeaciones.
                    </p>
                </div>

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
                action="<?= BASE_URL ?>/api/ejercicios/guardar.php"
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
                            placeholder="Ejemplo: Costal pesado"
                            value="<?= valorEjercicio(
                                $datosAnteriores,
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
                                    <?= $categoriaSeleccionada === $valor
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
                                    <?= $medicionSeleccionada === $valor
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $texto ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="field-help">
                            En la planeación podremos agregar también
                            duración, descanso, intensidad y otras medidas.
                        </small>

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
                            placeholder="Ejemplo: Mantener guardia alta y trabajar combinaciones de tres golpes."
                        ><?= valorEjercicio(
                            $datosAnteriores,
                            'descripcion'
                        ) ?></textarea>

                        <div class="character-help">
                            Máximo 500 caracteres.
                        </div>

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
                        Guardar ejercicio
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>