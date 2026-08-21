<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

$planeacionId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$planeacionId || $planeacionId < 1) {
    $_SESSION['mensaje_error'] =
        'La planeación seleccionada no es válida.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

$consulta = $pdo->prepare(
    'SELECT
        planeaciones.id,
        planeaciones.nombre,
        planeaciones.objetivo,
        planeaciones.fecha_inicio,
        planeaciones.fecha_fin,
        planeaciones.estado,
        planeaciones.observaciones,
        planeaciones.creado_en,
        planeaciones.actualizado_en,

        alumnos.id AS alumno_id,
        alumnos.numero_alumno,
        alumnos.nombres,
        alumnos.apellidos,
        alumnos.nivel,

        usuarios.nombre AS entrenador

    FROM planeaciones

    INNER JOIN alumnos
        ON alumnos.id = planeaciones.alumno_id

    INNER JOIN usuarios
        ON usuarios.id = planeaciones.creado_por

    WHERE planeaciones.id = :id
    LIMIT 1'
);

$consulta->execute([
    'id' => $planeacionId,
]);

$planeacion = $consulta->fetch();

if (!$planeacion) {
    $_SESSION['mensaje_error'] =
        'La planeación solicitada no existe.';

    header('Location: ' . BASE_URL . '/planeaciones/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar ejercicios asignados
|--------------------------------------------------------------------------
*/

$ejerciciosAsignados = [];
$ejerciciosPorDia = [];

$consultaEjercicios = $pdo->prepare(
    'SELECT
        planeacion_ejercicios.id,
        planeacion_ejercicios.ejercicio_id,
        planeacion_ejercicios.dia_semana,
        planeacion_ejercicios.orden,
        planeacion_ejercicios.series,
        planeacion_ejercicios.repeticiones,
        planeacion_ejercicios.rounds,
        planeacion_ejercicios.duracion_minutos,
        planeacion_ejercicios.descanso_segundos,
        planeacion_ejercicios.distancia_metros,
        planeacion_ejercicios.intensidad,
        planeacion_ejercicios.indicaciones,

        ejercicios.nombre AS ejercicio_nombre,
        ejercicios.categoria,
        ejercicios.tipo_medicion,

        usuarios.nombre AS agregado_por_nombre

    FROM planeacion_ejercicios

    INNER JOIN ejercicios
        ON ejercicios.id = planeacion_ejercicios.ejercicio_id

    INNER JOIN usuarios
        ON usuarios.id = planeacion_ejercicios.agregado_por

    WHERE planeacion_ejercicios.planeacion_id = :planeacion_id

    ORDER BY
        FIELD(
            planeacion_ejercicios.dia_semana,
            "lunes",
            "martes",
            "miercoles",
            "jueves",
            "viernes",
            "sabado",
            "domingo"
        ),
        planeacion_ejercicios.orden ASC,
        planeacion_ejercicios.id ASC'
);

$consultaEjercicios->execute([
    'planeacion_id' => $planeacionId,
]);

$ejerciciosAsignados = $consultaEjercicios->fetchAll();

/*
|--------------------------------------------------------------------------
| Agrupar ejercicios por día
|--------------------------------------------------------------------------
*/

foreach ($ejerciciosAsignados as $ejercicioAsignado) {
    $dia = $ejercicioAsignado['dia_semana'];

    if (!isset($ejerciciosPorDia[$dia])) {
        $ejerciciosPorDia[$dia] = [];
    }

    $ejerciciosPorDia[$dia][] = $ejercicioAsignado;
}

$nombresDias = [
    'lunes' => 'Lunes',
    'martes' => 'Martes',
    'miercoles' => 'Miércoles',
    'jueves' => 'Jueves',
    'viernes' => 'Viernes',
    'sabado' => 'Sábado',
    'domingo' => 'Domingo',
];

$nombresCategorias = [
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




$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

$claseEstado = match ($planeacion['estado']) {
    'activa' => 'badge-success',
    'borrador' => 'badge-warning',
    'terminada' => 'badge-neutral',
    'cancelada' => 'badge-danger',
    default => 'badge-neutral',
};

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Ver planeación | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/planeaciones.css?=v1">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Detalle de planeación</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/planeaciones/listar.php">
            Volver a planeaciones
        </a>

    </header>

    <main class="module-container">

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

        <section class="planning-detail-header">

            <div>

                <span class="badge <?= $claseEstado ?>">
                    <?= htmlspecialchars(
                        ucfirst($planeacion['estado']),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <h2>
                    <?= htmlspecialchars(
                        $planeacion['nombre'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <a
                    class="planning-detail-student"
                    href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $planeacion['alumno_id'] ?>">
                    <?= htmlspecialchars(
                        $planeacion['numero_alumno']
                            . ' - '
                            . $planeacion['nombres']
                            . ' '
                            . $planeacion['apellidos'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </a>

            </div>
            <div class="planning-main-actions">

                <?php if ($planeacion['estado'] === 'activa'): ?>

                    <a
                        class="btn-training"
                        href="<?= BASE_URL ?>/planeaciones/seguimiento.php?planeacion_id=<?= $planeacionId ?>&fecha=<?= date('Y-m-d') ?>">
                        Registrar entrenamiento
                    </a>

                <?php endif; ?>

                <?php if (in_array(
                    $planeacion['estado'],
                    ['borrador', 'activa'],
                    true
                )): ?>

                    <a
                        class="btn-primary"
                        href="<?= BASE_URL ?>/planeaciones/editar.php?id=<?= $planeacionId ?>">
                        Editar planeación
                    </a>

                <?php endif; ?>

                <?php if (
                    $planeacion['estado'] === 'borrador'
                ): ?>

                    <form
                        action="<?= BASE_URL ?>/api/planeaciones/cambiar_estado.php"
                        method="POST"
                        onsubmit="return confirm('¿Confirmas que deseas activar esta planeación?');">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $planeacionId ?>">

                        <input
                            type="hidden"
                            name="estado"
                            value="activa">

                        <button
                            class="btn-activate-plan"
                            type="submit">
                            Activar planeación
                        </button>

                    </form>

                <?php endif; ?>

                <?php if (
                    $planeacion['estado'] === 'activa'
                ): ?>

                    <form
                        action="<?= BASE_URL ?>/api/planeaciones/cambiar_estado.php"
                        method="POST"
                        onsubmit="return confirm('¿Confirmas que esta planeación fue terminada?');">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $planeacionId ?>">

                        <input
                            type="hidden"
                            name="estado"
                            value="terminada">

                        <button
                            class="btn-finish-plan"
                            type="submit">
                            Terminar planeación
                        </button>

                    </form>

                <?php endif; ?>

                <?php if (in_array(
                    $planeacion['estado'],
                    ['borrador', 'activa'],
                    true
                )): ?>

                    <form
                        action="<?= BASE_URL ?>/api/planeaciones/cambiar_estado.php"
                        method="POST"
                        onsubmit="return confirm('¿Confirmas que deseas cancelar esta planeación?');">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                        $_SESSION['csrf_token'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= $planeacionId ?>">

                        <input
                            type="hidden"
                            name="estado"
                            value="cancelada">

                        <button
                            class="btn-cancel-plan"
                            type="submit">
                            Cancelar planeación
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </section>

        <section class="detail-grid planning-detail-grid">

            <article class="detail-card">

                <h3>Información general</h3>

                <dl class="detail-list">

                    <div>
                        <dt>Fecha de inicio</dt>

                        <dd>
                            <?= date(
                                'd/m/Y',
                                strtotime(
                                    $planeacion['fecha_inicio']
                                )
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Fecha de finalización</dt>

                        <dd>
                            <?= $planeacion['fecha_fin']
                                ? date(
                                    'd/m/Y',
                                    strtotime(
                                        $planeacion['fecha_fin']
                                    )
                                )
                                : 'Sin definir' ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Entrenador</dt>

                        <dd>
                            <?= htmlspecialchars(
                                $planeacion['entrenador'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Nivel del alumno</dt>

                        <dd>
                            <?= htmlspecialchars(
                                ucfirst($planeacion['nivel']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                </dl>

            </article>

            <article class="detail-card">

                <h3>Objetivo</h3>

                <p class="detail-text">
                    <?= nl2br(
                        htmlspecialchars(
                            $planeacion['objetivo']
                                ?: 'No se registró un objetivo.',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>

            </article>

            <article class="detail-card detail-card-full">

                <h3>Observaciones generales</h3>

                <p class="detail-text">
                    <?= nl2br(
                        htmlspecialchars(
                            $planeacion['observaciones']
                                ?: 'No existen observaciones.',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>

            </article>

        </section>

        <section class="planning-exercises-section">

            <div class="planning-exercises-header">

                <div>
                    <h3>Ejercicios de la planeación</h3>

                    <p>
                        <?= count($ejerciciosAsignados) ?>
                        ejercicios asignados
                    </p>
                </div>

                <?php if (in_array(
                    $planeacion['estado'],
                    ['borrador', 'activa'],
                    true
                )): ?>

                    <a
                        class="btn-primary"
                        href="<?= BASE_URL ?>/planeaciones/agregar_ejercicio.php?planeacion_id=<?= $planeacionId ?>">
                        Agregar ejercicio
                    </a>

                <?php endif; ?>

            </div>

            <?php if (empty($ejerciciosAsignados)): ?>

                <div class="planning-exercises-empty">

                    <h4>Esta planeación todavía no tiene ejercicios</h4>

                    <p>
                        Agrega el primer ejercicio y selecciona el día
                        en que deberá realizarse.
                    </p>

                </div>

            <?php else: ?>

                <div class="planning-days">

                    <?php foreach (
                        $nombresDias as $diaValor => $diaNombre
                    ): ?>

                        <?php if (
                            empty($ejerciciosPorDia[$diaValor])
                        ): ?>

                            <?php continue; ?>

                        <?php endif; ?>

                        <article class="planning-day">

                            <div class="planning-day-header">

                                <h4>
                                    <?= $diaNombre ?>
                                </h4>

                                <span>
                                    <?= count(
                                        $ejerciciosPorDia[$diaValor]
                                    ) ?>
                                    ejercicios
                                </span>

                            </div>

                            <div class="planning-day-exercises">

                                <?php foreach (
                                    $ejerciciosPorDia[$diaValor]
                                    as $indiceEjercicio => $ejercicio
                                ): ?>

                                    <?php
                                    $esPrimero = $indiceEjercicio === 0;

                                    $esUltimo =
                                        $indiceEjercicio
                                        === count($ejerciciosPorDia[$diaValor]) - 1;
                                    ?>

                                    <?php
                                    $detalles = [];

                                    if ($ejercicio['series'] !== null) {
                                        $detalles[] =
                                            (int) $ejercicio['series']
                                            . ' series';
                                    }

                                    if (
                                        $ejercicio['repeticiones'] !== null
                                    ) {
                                        $detalles[] =
                                            (int) $ejercicio['repeticiones']
                                            . ' repeticiones';
                                    }

                                    if ($ejercicio['rounds'] !== null) {
                                        $detalles[] =
                                            (int) $ejercicio['rounds']
                                            . ' rounds';
                                    }

                                    if (
                                        $ejercicio['duracion_minutos']
                                        !== null
                                    ) {
                                        $detalles[] =
                                            rtrim(
                                                rtrim(
                                                    number_format(
                                                        (float) $ejercicio['duracion_minutos'],
                                                        2,
                                                        '.',
                                                        ''
                                                    ),
                                                    '0'
                                                ),
                                                '.'
                                            )
                                            . ' minutos';
                                    }

                                    if (
                                        $ejercicio['distancia_metros']
                                        !== null
                                    ) {
                                        $detalles[] =
                                            number_format(
                                                (float) $ejercicio['distancia_metros'],
                                                0
                                            )
                                            . ' metros';
                                    }

                                    if (
                                        $ejercicio['descanso_segundos']
                                        !== null
                                    ) {
                                        $detalles[] =
                                            (int) $ejercicio['descanso_segundos']
                                            . ' segundos de descanso';
                                    }

                                    $textoIntensidad = ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $ejercicio['intensidad']
                                        )
                                    );
                                    ?>

                                    <div class="assigned-exercise">

                                        <div class="assigned-exercise-order">
                                            <?= (int) $ejercicio['orden'] ?>
                                        </div>

                                        <div class="assigned-exercise-content">

                                            <div class="assigned-exercise-title">

                                                <div>
                                                    <h5>
                                                        <?= htmlspecialchars(
                                                            $ejercicio['ejercicio_nombre'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </h5>

                                                    <span>
                                                        <?= htmlspecialchars(
                                                            $nombresCategorias[$ejercicio['categoria']] ?? 'Otro',
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>
                                                    </span>
                                                </div>

                                                <span
                                                    class="intensity-badge intensity-<?= htmlspecialchars(
                                                                                            $ejercicio['intensidad'],
                                                                                            ENT_QUOTES,
                                                                                            'UTF-8'
                                                                                        ) ?>">
                                                    Intensidad:
                                                    <?= htmlspecialchars(
                                                        $textoIntensidad,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </span>

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

                                            <?php else: ?>

                                                <div class="exercise-measurements">

                                                    <span>
                                                        Medición libre
                                                    </span>

                                                </div>

                                            <?php endif; ?>

                                            <?php if (
                                                $ejercicio['indicaciones']
                                            ): ?>

                                                <p class="assigned-exercise-notes">
                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $ejercicio['indicaciones'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        )
                                                    ) ?>
                                                </p>

                                            <?php endif; ?>

                                            <?php if (in_array(
                                                $planeacion['estado'],
                                                ['borrador', 'activa'],
                                                true
                                            )): ?>

                                                <div class="assigned-exercise-actions">

                                                    <a
                                                        class="exercise-action exercise-action-edit"
                                                        href="<?= BASE_URL ?>/planeaciones/editar_ejercicio.php?id=<?= (int) $ejercicio['id'] ?>">
                                                        Editar
                                                    </a>

                                                    <?php if (!$esPrimero): ?>

                                                        <form
                                                            action="<?= BASE_URL ?>/api/planeaciones/mover_ejercicio.php"
                                                            method="POST">

                                                            <input
                                                                type="hidden"
                                                                name="csrf_token"
                                                                value="<?= htmlspecialchars(
                                                                            $_SESSION['csrf_token'],
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ) ?>">

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= (int) $ejercicio['id'] ?>">

                                                            <input
                                                                type="hidden"
                                                                name="direccion"
                                                                value="arriba">

                                                            <button
                                                                class="exercise-action exercise-action-move"
                                                                type="submit"
                                                                title="Mover hacia arriba">
                                                                ↑ Subir
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                    <?php if (!$esUltimo): ?>

                                                        <form
                                                            action="<?= BASE_URL ?>/api/planeaciones/mover_ejercicio.php"
                                                            method="POST">

                                                            <input
                                                                type="hidden"
                                                                name="csrf_token"
                                                                value="<?= htmlspecialchars(
                                                                            $_SESSION['csrf_token'],
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ) ?>">

                                                            <input
                                                                type="hidden"
                                                                name="id"
                                                                value="<?= (int) $ejercicio['id'] ?>">

                                                            <input
                                                                type="hidden"
                                                                name="direccion"
                                                                value="abajo">

                                                            <button
                                                                class="exercise-action exercise-action-move"
                                                                type="submit"
                                                                title="Mover hacia abajo">
                                                                ↓ Bajar
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                    <form
                                                        action="<?= BASE_URL ?>/api/planeaciones/retirar_ejercicio.php"
                                                        method="POST"
                                                        onsubmit="return confirm('¿Confirmas que deseas retirar este ejercicio de la planeación?');">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= htmlspecialchars(
                                                                        $_SESSION['csrf_token'],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ) ?>">

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int) $ejercicio['id'] ?>">

                                                        <button
                                                            class="exercise-action exercise-action-remove"
                                                            type="submit">
                                                            Retirar
                                                        </button>

                                                    </form>

                                                </div>

                                            <?php endif; ?>




                                            <small class="assigned-exercise-user">
                                                Agregado por:
                                                <?= htmlspecialchars(
                                                    $ejercicio['agregado_por_nombre'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </small>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>

</html>