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
        p.id,
        p.alumno_id,
        p.nombre,
        p.objetivo,
        p.fecha_inicio,
        p.fecha_fin,
        p.estado,
        p.observaciones,

        a.numero_alumno,
        a.nombres,
        a.apellidos

    FROM planeaciones AS p

    INNER JOIN alumnos AS a
        ON a.id = p.alumno_id

    WHERE p.id = :id
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
| Solo borradores y activas se pueden modificar
|--------------------------------------------------------------------------
*/

if (!in_array(
    $planeacion['estado'],
    ['borrador', 'activa'],
    true
)) {
    $_SESSION['mensaje_error'] =
        'Una planeación terminada o cancelada ya no puede editarse.';

    header(
        'Location: '
        . BASE_URL
        . '/planeaciones/ver.php?id='
        . $planeacionId
    );

    exit;
}

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_edicion_planeacion'] ?? null;

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_edicion_planeacion']
);

if (
    is_array($datosAnteriores)
    && (int) ($datosAnteriores['id'] ?? 0) === $planeacionId
) {
    $datos = array_merge(
        $planeacion,
        $datosAnteriores
    );
} else {
    $datos = $planeacion;
}

function valorEditarPlaneacion(
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

    <title>Editar planeación | Gym Box</title>

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
        <p>Editar planeación</p>
    </div>

    <a
        class="btn-secondary"
        href="<?= BASE_URL ?>/planeaciones/ver.php?id=<?= $planeacionId ?>"
    >
        Volver
    </a>

</header>

<main class="module-container">

    <section class="form-card">

        <div class="module-header">

            <div>

                <h2>Editar planeación</h2>

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
            action="<?= BASE_URL ?>/api/planeaciones/actualizar.php"
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
                value="<?= $planeacionId ?>"
            >

            <div class="form-grid">

                <div class="form-group form-group-full">

                    <label for="nombre">
                        Nombre de la planeación *
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="150"
                        value="<?= valorEditarPlaneacion(
                            $datos,
                            'nombre'
                        ) ?>"
                        required
                        autofocus
                    >

                </div>

                <div class="form-group">

                    <label for="fecha_inicio">
                        Fecha de inicio *
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        id="fecha_inicio"
                        name="fecha_inicio"
                        value="<?= valorEditarPlaneacion(
                            $datos,
                            'fecha_inicio'
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="fecha_fin">
                        Fecha de finalización
                    </label>

                    <input
                        class="form-control"
                        type="date"
                        id="fecha_fin"
                        name="fecha_fin"
                        value="<?= valorEditarPlaneacion(
                            $datos,
                            'fecha_fin'
                        ) ?>"
                    >

                </div>

                <div class="form-group form-group-full">

                    <label for="objetivo">
                        Objetivo
                    </label>

                    <textarea
                        class="form-control"
                        id="objetivo"
                        name="objetivo"
                        rows="4"
                        maxlength="500"
                    ><?= valorEditarPlaneacion(
                        $datos,
                        'objetivo'
                    ) ?></textarea>

                </div>

                <div class="form-group form-group-full">

                    <label for="observaciones">
                        Observaciones generales
                    </label>

                    <textarea
                        class="form-control"
                        id="observaciones"
                        name="observaciones"
                        rows="5"
                        maxlength="3000"
                    ><?= valorEditarPlaneacion(
                        $datos,
                        'observaciones'
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

</body>
</html>