<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

/*
|--------------------------------------------------------------------------
| Validar planeación
|--------------------------------------------------------------------------
*/

$planeacionId = filter_input(
    INPUT_GET,
    'planeacion_id',
    FILTER_VALIDATE_INT
);

if (!$planeacionId || $planeacionId < 1) {
    $_SESSION['mensaje_error'] =
        'La planeación seleccionada no es válida.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar planeación
|--------------------------------------------------------------------------
*/

$consultaPlaneacion = $pdo->prepare(
    'SELECT
        planeaciones.id,
        planeaciones.nombre,
        planeaciones.estado,

        alumnos.numero_alumno,
        alumnos.nombres,
        alumnos.apellidos

    FROM planeaciones

    INNER JOIN alumnos
        ON alumnos.id = planeaciones.alumno_id

    WHERE planeaciones.id = :id
    LIMIT 1'
);

$consultaPlaneacion->execute([
    'id' => $planeacionId,
]);

$planeacion = $consultaPlaneacion->fetch();

if (!$planeacion) {
    $_SESSION['mensaje_error'] =
        'La planeación solicitada no existe.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Comprobar que todavía pueda modificarse
|--------------------------------------------------------------------------
*/

if (!in_array(
    $planeacion['estado'],
    ['borrador', 'activa'],
    true
)) {
    $_SESSION['mensaje_error'] =
        'No puedes agregar ejercicios a una planeación '
        . 'terminada o cancelada.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/ver.php?id='
        . $planeacionId
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar ejercicios activos
|--------------------------------------------------------------------------
*/

$consultaEjercicios = $pdo->query(
    'SELECT
        id,
        nombre,
        categoria,
        tipo_medicion,
        descripcion

    FROM ejercicios

    WHERE activo = 1

    ORDER BY
        categoria ASC,
        nombre ASC'
);

$ejercicios = $consultaEjercicios->fetchAll();

/*
|--------------------------------------------------------------------------
| Recuperar formulario anterior
|--------------------------------------------------------------------------
*/

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_planeacion_ejercicio'] ?? [];

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_planeacion_ejercicio']
);

function valorPlaneacionEjercicio(
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

$diaSeleccionado =
    $datosAnteriores['dia_semana'] ?? 'lunes';

$intensidadSeleccionada =
    $datosAnteriores['intensidad'] ?? 'media';

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Agregar ejercicio | Gym Box</title>

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
        href="<?= BASE_URL ?>/css/planeaciones.css"
    >
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Ejercicios de la planeación</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/planeaciones/ver.php?id=<?= $planeacionId ?>"
        >
            Volver a la planeación
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">

                <div>
                    <h2>Agregar ejercicio</h2>

                    <p>
                        <?= htmlspecialchars(
                            $planeacion['nombre'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        Alumno:
                        <strong>
                            <?= htmlspecialchars(
                                $planeacion['numero_alumno']
                                . ' - '
                                . $planeacion['nombres']
                                . ' '
                                . $planeacion['apellidos'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
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

            <?php if (empty($ejercicios)): ?>

                <div class="alert alert-error">
                    No existen ejercicios activos en el catálogo.
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/ejercicios/crear.php"
                >
                    Registrar ejercicio
                </a>

            <?php else: ?>

                <form
                    action="<?= BASE_URL ?>/api/planeaciones/guardar_ejercicio.php"
                    method="POST"
                    id="form-planeacion-ejercicio"
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
                        name="planeacion_id"
                        value="<?= $planeacionId ?>"
                    >

                    <div class="form-grid">

                        <div class="form-group form-group-full">

                            <label for="ejercicio_id">
                                Ejercicio *
                            </label>

                            <select
                                class="form-control"
                                id="ejercicio_id"
                                name="ejercicio_id"
                                required
                            >

                                <option value="">
                                    Selecciona un ejercicio
                                </option>

                                <?php foreach (
                                    $ejercicios as $ejercicio
                                ): ?>

                                    <option
                                        value="<?= (int) $ejercicio['id'] ?>"
                                        data-medicion="<?= htmlspecialchars(
                                            $ejercicio['tipo_medicion'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-descripcion="<?= htmlspecialchars(
                                            $ejercicio['descripcion'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= (string) (
                                            $datosAnteriores['ejercicio_id']
                                            ?? ''
                                        ) === (string) $ejercicio['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $ejercicio['nombre']
                                            . ' - '
                                            . (
                                                $categorias[
                                                    $ejercicio['categoria']
                                                ] ?? 'Otro'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div
                                class="exercise-selection-help"
                                id="exercise-selection-help"
                            >
                                Selecciona un ejercicio para ver
                                cómo se mide.
                            </div>

                        </div>

                        <div class="form-group">

                            <label for="dia_semana">
                                Día de entrenamiento *
                            </label>

                            <select
                                class="form-control"
                                id="dia_semana"
                                name="dia_semana"
                                required
                            >

                                <?php
                                $dias = [
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo',
                                ];
                                ?>

                                <?php foreach ($dias as $valor => $texto): ?>

                                    <option
                                        value="<?= $valor ?>"
                                        <?= $diaSeleccionado === $valor
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $texto ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="form-group">

                            <label for="intensidad">
                                Intensidad *
                            </label>

                            <select
                                class="form-control"
                                id="intensidad"
                                name="intensidad"
                                required
                            >

                                <?php
                                $intensidades = [
                                    'baja' => 'Baja',
                                    'media' => 'Media',
                                    'alta' => 'Alta',
                                    'muy_alta' => 'Muy alta',
                                ];
                                ?>

                                <?php foreach (
                                    $intensidades as $valor => $texto
                                ): ?>

                                    <option
                                        value="<?= $valor ?>"
                                        <?= $intensidadSeleccionada === $valor
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $texto ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div
                            class="form-group measurement-field"
                            data-measurement-field="series"
                        >

                            <label for="series">
                                Series
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="series"
                                name="series"
                                min="1"
                                max="999"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'series'
                                ) ?>"
                            >

                        </div>

                        <div
                            class="form-group measurement-field"
                            data-measurement-field="repeticiones"
                        >

                            <label for="repeticiones">
                                Repeticiones
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="repeticiones"
                                name="repeticiones"
                                min="1"
                                max="9999"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'repeticiones'
                                ) ?>"
                            >

                        </div>

                        <div
                            class="form-group measurement-field"
                            data-measurement-field="rounds"
                        >

                            <label for="rounds">
                                Número de rounds
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="rounds"
                                name="rounds"
                                min="1"
                                max="999"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'rounds'
                                ) ?>"
                            >

                        </div>

                        <div
                            class="form-group measurement-field"
                            data-measurement-field="duracion"
                        >

                            <label for="duracion_minutos">
                                Duración en minutos
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="duracion_minutos"
                                name="duracion_minutos"
                                min="0.01"
                                max="9999"
                                step="0.01"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'duracion_minutos'
                                ) ?>"
                            >

                            <small class="field-help">
                                En ejercicios por rounds, corresponde
                                a la duración de cada round.
                            </small>

                        </div>

                        <div
                            class="form-group measurement-field"
                            data-measurement-field="distancia"
                        >

                            <label for="distancia_metros">
                                Distancia en metros
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="distancia_metros"
                                name="distancia_metros"
                                min="0.01"
                                max="999999"
                                step="0.01"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'distancia_metros'
                                ) ?>"
                            >

                        </div>

                        <div class="form-group">

                            <label for="descanso_segundos">
                                Descanso en segundos
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="descanso_segundos"
                                name="descanso_segundos"
                                min="1"
                                max="9999"
                                value="<?= valorPlaneacionEjercicio(
                                    $datosAnteriores,
                                    'descanso_segundos'
                                ) ?>"
                            >

                        </div>

                        <div class="form-group form-group-full">

                            <label for="indicaciones">
                                Indicaciones personalizadas
                            </label>

                            <textarea
                                class="form-control"
                                id="indicaciones"
                                name="indicaciones"
                                rows="5"
                                maxlength="500"
                                placeholder="Ejemplo: Mantener guardia alta y trabajar combinaciones de tres golpes."
                            ><?= valorPlaneacionEjercicio(
                                $datosAnteriores,
                                'indicaciones'
                            ) ?></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            class="btn-secondary"
                            href="<?= BASE_URL ?>/planeaciones/ver.php?id=<?= $planeacionId ?>"
                        >
                            Cancelar
                        </a>

                        <button
                            class="btn-primary"
                            type="submit"
                        >
                            Agregar ejercicio
                        </button>

                    </div>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <script src="<?= BASE_URL ?>/js/planeaciones.js"></script>

</body>
</html>