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

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/listar.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Fecha del entrenamiento
|--------------------------------------------------------------------------
*/

$fechaSeleccionada = trim(
    (string) ($_GET['fecha'] ?? date('Y-m-d'))
);

function fechaSeguimientoValida(string $fecha): bool
{
    $objeto = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $objeto !== false
        && $objeto->format('Y-m-d') === $fecha;
}

if (!fechaSeguimientoValida($fechaSeleccionada)) {
    $fechaSeleccionada = date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| Consultar planeación
|--------------------------------------------------------------------------
*/

$consultaPlaneacion = $pdo->prepare(
    'SELECT
        p.id,
        p.nombre,
        p.fecha_inicio,
        p.fecha_fin,
        p.estado,

        a.id AS alumno_id,
        a.numero_alumno,
        a.nombres,
        a.apellidos,
        a.nivel

    FROM planeaciones AS p

    INNER JOIN alumnos AS a
        ON a.id = p.alumno_id

    WHERE p.id = :id
    LIMIT 1'
);

$consultaPlaneacion->execute([
    'id' => $planeacionId,
]);

$planeacion = $consultaPlaneacion->fetch();

if (!$planeacion) {
    $_SESSION['mensaje_error'] =
        'La planeación solicitada no existe.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/listar.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Solo las planeaciones activas reciben seguimiento
|--------------------------------------------------------------------------
*/

if ($planeacion['estado'] !== 'activa') {
    $_SESSION['mensaje_error'] =
        'Solo puedes registrar seguimiento '
        . 'en una planeación activa.';

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
| Comprobar que la fecha pertenezca a la planeación
|--------------------------------------------------------------------------
*/

$fechaFueraDeRango = false;

if ($fechaSeleccionada < $planeacion['fecha_inicio']) {
    $fechaFueraDeRango = true;
}

if (
    $planeacion['fecha_fin']
    && $fechaSeleccionada > $planeacion['fecha_fin']
) {
    $fechaFueraDeRango = true;
}

/*
|--------------------------------------------------------------------------
| Obtener día correspondiente
|--------------------------------------------------------------------------
*/

$numeroDia = (int) date(
    'N',
    strtotime($fechaSeleccionada)
);

$diasPorNumero = [
    1 => 'lunes',
    2 => 'martes',
    3 => 'miercoles',
    4 => 'jueves',
    5 => 'viernes',
    6 => 'sabado',
    7 => 'domingo',
];

$nombresDias = [
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo',
];

$diaSemana =
    $diasPorNumero[$numeroDia] ?? 'lunes';

/*
|--------------------------------------------------------------------------
| Consultar ejercicios de ese día
|--------------------------------------------------------------------------
*/

$consultaEjercicios = $pdo->prepare(
    'SELECT
        pe.id,
        pe.orden,
        pe.series,
        pe.repeticiones,
        pe.rounds,
        pe.duracion_minutos,
        pe.descanso_segundos,
        pe.distancia_metros,
        pe.intensidad,
        pe.indicaciones,

        e.nombre AS ejercicio_nombre,
        e.categoria

    FROM planeacion_ejercicios AS pe

    INNER JOIN ejercicios AS e
        ON e.id = pe.ejercicio_id

    WHERE pe.planeacion_id = :planeacion_id
        AND pe.dia_semana = :dia

    ORDER BY
        pe.orden ASC,
        pe.id ASC'
);

$consultaEjercicios->execute([
    'planeacion_id' => $planeacionId,
    'dia' => $diaSemana,
]);

$ejercicios = $consultaEjercicios->fetchAll();

/*
|--------------------------------------------------------------------------
| Consultar sesión existente
|--------------------------------------------------------------------------
*/

$consultaSesion = $pdo->prepare(
    'SELECT
        id,
        observaciones
    FROM sesiones_entrenamiento
    WHERE planeacion_id = :planeacion_id
        AND fecha = :fecha
    LIMIT 1'
);

$consultaSesion->execute([
    'planeacion_id' => $planeacionId,
    'fecha' => $fechaSeleccionada,
]);

$sesion = $consultaSesion->fetch();

$seguimientos = [];

if ($sesion) {

    $consultaSeguimientos = $pdo->prepare(
        'SELECT
            planeacion_ejercicio_id,
            estado,
            observacion
        FROM seguimiento_ejercicios
        WHERE sesion_id = :sesion_id'
    );

    $consultaSeguimientos->execute([
        'sesion_id' => $sesion['id'],
    ]);

    foreach (
        $consultaSeguimientos->fetchAll()
        as $seguimiento
    ) {
        $seguimientos[
            (int) $seguimiento['planeacion_ejercicio_id']
        ] = $seguimiento;
    }
}

/*
|--------------------------------------------------------------------------
| Mensajes
|--------------------------------------------------------------------------
*/

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

function textoIntensidadSeguimiento(string $valor): string
{
    return match ($valor) {
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'muy_alta' => 'Muy alta',
        default => $valor,
    };
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

    <title>Seguimiento | Gym Box</title>

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
        <p>Seguimiento del entrenamiento</p>
    </div>

    <a
        class="btn-secondary"
        href="<?= BASE_URL ?>/planeaciones/ver.php?id=<?= $planeacionId ?>"
    >
        Volver a la planeación
    </a>

</header>

<main class="module-container">

    <section class="training-header">

        <div>

            <span class="training-day">
                <?= $nombresDias[$diaSemana] ?>
            </span>

            <h2>
                <?= htmlspecialchars(
                    $planeacion['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h2>

            <p>
                <?= htmlspecialchars(
                    $planeacion['numero_alumno']
                    . ' - '
                    . $planeacion['nombres']
                    . ' '
                    . $planeacion['apellidos'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>

    </section>

    <?php if ($mensajeExito): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars(
                $mensajeExito,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>

    <?php if ($mensajeError): ?>

        <div class="alert alert-error">
            <?= htmlspecialchars(
                $mensajeError,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <section class="training-date-card">

        <form
            method="GET"
            action="<?= BASE_URL ?>/planeaciones/seguimiento.php"
            class="training-date-form"
        >

            <input
                type="hidden"
                name="planeacion_id"
                value="<?= $planeacionId ?>"
            >

            <div>

                <label for="fecha">
                    Fecha del entrenamiento
                </label>

                <input
                    class="form-control"
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="<?= htmlspecialchars(
                        $fechaSeleccionada,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>

            <button
                class="btn-primary"
                type="submit"
            >
                Ver entrenamiento
            </button>

        </form>

    </section>


    <?php if ($fechaFueraDeRango): ?>

        <div class="alert alert-error">
            La fecha seleccionada se encuentra fuera
            del periodo de esta planeación.
        </div>

    <?php elseif (empty($ejercicios)): ?>

        <section class="training-empty">

            <h3>
                No hay ejercicios para
                <?= $nombresDias[$diaSemana] ?>
            </h3>

            <p>
                Esta planeación no tiene ejercicios
                asignados para ese día.
            </p>

        </section>

    <?php else: ?>

        <form
            action="<?= BASE_URL ?>/api/planeaciones/guardar_seguimiento.php"
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
                name="planeacion_id"
                value="<?= $planeacionId ?>"
            >

            <input
                type="hidden"
                name="fecha"
                value="<?= htmlspecialchars(
                    $fechaSeleccionada,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <section class="training-exercise-list">

                <?php foreach ($ejercicios as $ejercicio): ?>

                    <?php

                    $ejercicioId = (int) $ejercicio['id'];

                    $seguimiento =
                        $seguimientos[$ejercicioId] ?? null;

                    $estadoActual =
                        $seguimiento['estado']
                        ?? 'pendiente';

                    $observacionActual =
                        $seguimiento['observacion']
                        ?? '';

                    $detalles = [];

                    if ($ejercicio['series'] !== null) {
                        $detalles[] =
                            $ejercicio['series']
                            . ' series';
                    }

                    if ($ejercicio['repeticiones'] !== null) {
                        $detalles[] =
                            $ejercicio['repeticiones']
                            . ' repeticiones';
                    }

                    if ($ejercicio['rounds'] !== null) {
                        $detalles[] =
                            $ejercicio['rounds']
                            . ' rounds';
                    }

                    if (
                        $ejercicio['duracion_minutos']
                        !== null
                    ) {
                        $detalles[] =
                            $ejercicio['duracion_minutos']
                            . ' min';
                    }

                    if (
                        $ejercicio['distancia_metros']
                        !== null
                    ) {
                        $detalles[] =
                            $ejercicio['distancia_metros']
                            . ' m';
                    }

                    if (
                        $ejercicio['descanso_segundos']
                        !== null
                    ) {
                        $detalles[] =
                            $ejercicio['descanso_segundos']
                            . ' s descanso';
                    }

                    ?>

                    <article class="training-exercise-card">

                        <div class="training-exercise-number">
                            <?= (int) $ejercicio['orden'] ?>
                        </div>

                        <div class="training-exercise-body">

                            <div class="training-exercise-top">

                                <div>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $ejercicio[
                                                'ejercicio_nombre'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <span>
                                        Intensidad:
                                        <?= htmlspecialchars(
                                            textoIntensidadSeguimiento(
                                                $ejercicio[
                                                    'intensidad'
                                                ]
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                </div>

                            </div>

                            <?php if (!empty($detalles)): ?>

                                <div class="exercise-measurements">

                                    <?php foreach (
                                        $detalles as $detalle
                                    ): ?>

                                        <span>
                                            <?= htmlspecialchars(
                                                $detalle,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                $ejercicio['indicaciones']
                            ): ?>

                                <div class="training-instructions">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $ejercicio[
                                                'indicaciones'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>


                            <div class="training-control">

                                <div class="form-group">

                                    <label>
                                        Resultado
                                    </label>

                                    <select
                                        class="form-control"
                                        name="estado[<?= $ejercicioId ?>]"
                                    >

                                        <option
                                            value="pendiente"
                                            <?= $estadoActual === 'pendiente'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Pendiente
                                        </option>

                                        <option
                                            value="completado"
                                            <?= $estadoActual === 'completado'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Completado
                                        </option>

                                        <option
                                            value="parcial"
                                            <?= $estadoActual === 'parcial'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Parcial
                                        </option>

                                        <option
                                            value="no_realizado"
                                            <?= $estadoActual === 'no_realizado'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            No realizado
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>
                                        Observación
                                    </label>

                                    <textarea
                                        class="form-control"
                                        name="observacion[<?= $ejercicioId ?>]"
                                        rows="2"
                                        maxlength="500"
                                        placeholder="¿Cómo respondió el alumno?"
                                    ><?= htmlspecialchars(
                                        $observacionActual,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?></textarea>

                                </div>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>


            <section class="training-general-notes">

                <label for="observaciones_generales">
                    Observaciones generales del entrenamiento
                </label>

                <textarea
                    class="form-control"
                    id="observaciones_generales"
                    name="observaciones_generales"
                    rows="4"
                    maxlength="1000"
                    placeholder="Ejemplo: Terminó cansado pero mantuvo buena técnica."
                ><?= htmlspecialchars(
                    $sesion['observaciones'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </section>


            <div class="training-save">

                <button
                    class="btn-primary"
                    type="submit"
                >
                    Guardar seguimiento
                </button>

            </div>

        </form>

    <?php endif; ?>

</main>

</body>
</html>