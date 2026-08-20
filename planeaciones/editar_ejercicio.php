<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

$asignacionId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$asignacionId || $asignacionId < 1) {
    $_SESSION['mensaje_error'] =
        'El ejercicio asignado no es válido.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar asignación
|--------------------------------------------------------------------------
*/

$consulta = $pdo->prepare(
    'SELECT
        pe.id,
        pe.planeacion_id,
        pe.ejercicio_id,
        pe.dia_semana,
        pe.orden,
        pe.series,
        pe.repeticiones,
        pe.rounds,
        pe.duracion_minutos,
        pe.descanso_segundos,
        pe.distancia_metros,
        pe.intensidad,
        pe.indicaciones,

        p.nombre AS planeacion_nombre,
        p.estado AS planeacion_estado,

        e.nombre AS ejercicio_nombre,
        e.tipo_medicion,

        a.numero_alumno,
        a.nombres,
        a.apellidos

    FROM planeacion_ejercicios AS pe

    INNER JOIN planeaciones AS p
        ON p.id = pe.planeacion_id

    INNER JOIN ejercicios AS e
        ON e.id = pe.ejercicio_id

    INNER JOIN alumnos AS a
        ON a.id = p.alumno_id

    WHERE pe.id = :id
    LIMIT 1'
);

$consulta->execute([
    'id' => $asignacionId,
]);

$asignacion = $consulta->fetch();

if (!$asignacion) {
    $_SESSION['mensaje_error'] =
        'El ejercicio asignado no existe.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$planeacionId = (int) $asignacion['planeacion_id'];

if (!in_array(
    $asignacion['planeacion_estado'],
    ['borrador', 'activa'],
    true
)) {
    $_SESSION['mensaje_error'] =
        'No se pueden editar ejercicios de una planeación '
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
| Consultar catálogo
|--------------------------------------------------------------------------
|
| Mostramos los ejercicios activos y también el ejercicio actual, aunque
| haya sido desactivado posteriormente.
|
*/

$consultaEjercicios = $pdo->prepare(
    'SELECT
        id,
        nombre,
        categoria,
        tipo_medicion,
        descripcion,
        activo
    FROM ejercicios
    WHERE activo = 1
        OR id = :ejercicio_actual
    ORDER BY categoria ASC, nombre ASC'
);

$consultaEjercicios->execute([
    'ejercicio_actual' => $asignacion['ejercicio_id'],
]);

$ejercicios = $consultaEjercicios->fetchAll();

/*
|--------------------------------------------------------------------------
| Recuperar formulario anterior
|--------------------------------------------------------------------------
*/

$mensajeError = $_SESSION['mensaje_error'] ?? null;

$datosAnteriores =
    $_SESSION['datos_edicion_planeacion_ejercicio'] ?? null;

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_edicion_planeacion_ejercicio']
);

if (
    is_array($datosAnteriores)
    && (int) ($datosAnteriores['id'] ?? 0) === $asignacionId
) {
    $datos = array_merge($asignacion, $datosAnteriores);
} else {
    $datos = $asignacion;
}

function valorAsignacion(
    array $datos,
    string $campo
): string {
    return htmlspecialchars(
        (string) ($datos[$campo] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$dias = [
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo',
];

$intensidades = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'muy_alta' => 'Muy alta',
];

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

    <title>Editar ejercicio asignado | Gym Box</title>

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
            <p>Editar ejercicio asignado</p>
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
                    <h2>Editar ejercicio</h2>

                    <p>
                        <?= htmlspecialchars(
                            $asignacion['planeacion_nombre'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        Alumno:
                        <strong>
                            <?= htmlspecialchars(
                                $asignacion['numero_alumno']
                                . ' - '
                                . $asignacion['nombres']
                                . ' '
                                . $asignacion['apellidos'],
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

            <form
                action="<?= BASE_URL ?>/api/planeaciones/actualizar_ejercicio.php"
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
                    name="id"
                    value="<?= $asignacionId ?>"
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

                            <?php foreach ($ejercicios as $ejercicio): ?>

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
                                    <?= (string) $datos['ejercicio_id']
                                        === (string) $ejercicio['id']
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
                                        )
                                        . (
                                            (int) $ejercicio['activo'] === 0
                                            ? ' (inactivo)'
                                            : ''
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
                            Selecciona un ejercicio para ver su medición.
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

                            <?php foreach ($dias as $valor => $texto): ?>

                                <option
                                    value="<?= $valor ?>"
                                    <?= $datos['dia_semana'] === $valor
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

                            <?php foreach (
                                $intensidades as $valor => $texto
                            ): ?>

                                <option
                                    value="<?= $valor ?>"
                                    <?= $datos['intensidad'] === $valor
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
                            value="<?= valorAsignacion(
                                $datos,
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
                            value="<?= valorAsignacion(
                                $datos,
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
                            value="<?= valorAsignacion(
                                $datos,
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
                            value="<?= valorAsignacion(
                                $datos,
                                'duracion_minutos'
                            ) ?>"
                        >

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
                            value="<?= valorAsignacion(
                                $datos,
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
                            value="<?= valorAsignacion(
                                $datos,
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
                        ><?= valorAsignacion(
                            $datos,
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
                        Guardar cambios
                    </button>

                </div>

            </form>

        </section>

    </main>

    <script src="<?= BASE_URL ?>/js/planeaciones.js"></script>

</body>
</html>