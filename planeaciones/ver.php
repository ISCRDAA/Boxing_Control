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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ver planeación | Gym Box</title>

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
            <p>Detalle de planeación</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/planeaciones/listar.php"
        >
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
                    href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $planeacion['alumno_id'] ?>"
                >
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

        <section class="planning-exercises-placeholder">

            <h3>Ejercicios de la planeación</h3>

            <p>
                Esta planeación todavía no tiene ejercicios asignados.
            </p>

            <span>
                En el siguiente paso agregaremos ejercicios organizados
                por día, series, repeticiones, rounds, duración y descanso.
            </span>

        </section>

    </main>

</body>
</html>