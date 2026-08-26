<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();
$seccionActiva = 'planeaciones';

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

$busqueda = trim(
    (string) ($_GET['q'] ?? '')
);

$estado = trim(
    (string) ($_GET['estado'] ?? 'todos')
);

$estadosPermitidos = [
    'todos',
    'borrador',
    'activa',
    'terminada',
    'cancelada',
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'todos';
}

/*
|--------------------------------------------------------------------------
| Resumen
|--------------------------------------------------------------------------
*/

$consultaResumen = $pdo->query(
    'SELECT
        COUNT(*) AS total,

        SUM(
            CASE WHEN estado = "borrador"
            THEN 1 ELSE 0 END
        ) AS borradores,

        SUM(
            CASE WHEN estado = "activa"
            THEN 1 ELSE 0 END
        ) AS activas,

        SUM(
            CASE WHEN estado = "terminada"
            THEN 1 ELSE 0 END
        ) AS terminadas

    FROM planeaciones'
);

$resumen = $consultaResumen->fetch();

/*
|--------------------------------------------------------------------------
| Construir consulta
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        planeaciones.id,
        planeaciones.nombre,
        planeaciones.objetivo,
        planeaciones.fecha_inicio,
        planeaciones.fecha_fin,
        planeaciones.estado,
        planeaciones.creado_en,

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

    WHERE 1 = 1
';

$parametros = [];

if ($busqueda !== '') {
    $sql .= '
        AND (
            planeaciones.nombre LIKE :busqueda_nombre
            OR planeaciones.objetivo LIKE :busqueda_objetivo
            OR alumnos.numero_alumno LIKE :busqueda_numero
            OR alumnos.nombres LIKE :busqueda_nombres
            OR alumnos.apellidos LIKE :busqueda_apellidos
            OR CONCAT(
                alumnos.nombres,
                " ",
                alumnos.apellidos
            ) LIKE :busqueda_completa
        )
    ';

    $textoBusqueda = '%' . $busqueda . '%';

    $parametros['busqueda_nombre'] = $textoBusqueda;
    $parametros['busqueda_objetivo'] = $textoBusqueda;
    $parametros['busqueda_numero'] = $textoBusqueda;
    $parametros['busqueda_nombres'] = $textoBusqueda;
    $parametros['busqueda_apellidos'] = $textoBusqueda;
    $parametros['busqueda_completa'] = $textoBusqueda;
}

if ($estado !== 'todos') {
    $sql .= '
        AND planeaciones.estado = :estado
    ';

    $parametros['estado'] = $estado;
}

$sql .= '
    ORDER BY
        CASE planeaciones.estado
            WHEN "activa" THEN 1
            WHEN "borrador" THEN 2
            WHEN "terminada" THEN 3
            WHEN "cancelada" THEN 4
        END,
        planeaciones.fecha_inicio DESC,
        planeaciones.id DESC
';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$planeaciones = $consulta->fetchAll();

function claseEstadoPlaneacion(string $estado): string
{
    return match ($estado) {
        'activa' => 'badge-success',
        'borrador' => 'badge-warning',
        'terminada' => 'badge-neutral',
        'cancelada' => 'badge-danger',
        default => 'badge-neutral',
    };
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Planeaciones | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/planeaciones.css">
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/mobile.css">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Planeaciones personalizadas</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/dashboard.php">
            Volver al panel
        </a>

    </header>

    <main class="module-container">

        <section class="module-card">

            <div class="module-header">

                <div>
                    <h2>Planeaciones registradas</h2>

                    <p>
                        Consulta los entrenamientos personalizados
                        de cada alumno.
                    </p>
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/planeaciones/crear.php">
                    Nueva planeación
                </a>

            </div>

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

            <div class="planning-summary">

                <article>
                    <span>Total</span>

                    <strong>
                        <?= (int) ($resumen['total'] ?? 0) ?>
                    </strong>
                </article>

                <article>
                    <span>Activas</span>

                    <strong>
                        <?= (int) ($resumen['activas'] ?? 0) ?>
                    </strong>
                </article>

                <article>
                    <span>Borradores</span>

                    <strong>
                        <?= (int) ($resumen['borradores'] ?? 0) ?>
                    </strong>
                </article>

                <article>
                    <span>Terminadas</span>

                    <strong>
                        <?= (int) ($resumen['terminadas'] ?? 0) ?>
                    </strong>
                </article>

            </div>

            <form
                class="planning-filters"
                method="GET"
                action="<?= BASE_URL ?>/planeaciones/listar.php">

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    placeholder="Buscar alumno, objetivo o planeación"
                    value="<?= htmlspecialchars(
                                $busqueda,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                <select
                    class="form-control"
                    name="estado">

                    <option
                        value="todos"
                        <?= $estado === 'todos'
                            ? 'selected'
                            : '' ?>>
                        Todos los estados
                    </option>

                    <option
                        value="borrador"
                        <?= $estado === 'borrador'
                            ? 'selected'
                            : '' ?>>
                        Borradores
                    </option>

                    <option
                        value="activa"
                        <?= $estado === 'activa'
                            ? 'selected'
                            : '' ?>>
                        Activas
                    </option>

                    <option
                        value="terminada"
                        <?= $estado === 'terminada'
                            ? 'selected'
                            : '' ?>>
                        Terminadas
                    </option>

                    <option
                        value="cancelada"
                        <?= $estado === 'cancelada'
                            ? 'selected'
                            : '' ?>>
                        Canceladas
                    </option>

                </select>

                <button
                    class="btn-primary"
                    type="submit">
                    Buscar
                </button>

                <?php if (
                    $busqueda !== ''
                    || $estado !== 'todos'
                ): ?>

                    <a
                        class="btn-secondary"
                        href="<?= BASE_URL ?>/planeaciones/listar.php">
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <div class="result-summary">
                Resultados encontrados:
                <strong><?= count($planeaciones) ?></strong>
            </div>

            <?php if (empty($planeaciones)): ?>

                <div class="empty-state">

                    <h3>No existen planeaciones</h3>

                    <p>
                        Crea la primera planeación personalizada.
                    </p>

                </div>

            <?php else: ?>

                <div class="planning-list">

                    <?php foreach ($planeaciones as $planeacion): ?>

                        <article class="planning-card">

                            <div class="planning-card-header">

                                <div>

                                    <span
                                        class="badge <?= claseEstadoPlaneacion(
                                                            $planeacion['estado']
                                                        ) ?>">
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $planeacion['estado']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $planeacion['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                </div>

                                <a
                                    class="btn-small btn-view"
                                    href="<?= BASE_URL ?>/planeaciones/ver.php?id=<?= (int) $planeacion['id'] ?>">
                                    Abrir
                                </a>

                            </div>

                            <div class="planning-student">

                                <a
                                    href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $planeacion['alumno_id'] ?>">
                                    <?= htmlspecialchars(
                                        $planeacion['nombres']
                                            . ' '
                                            . $planeacion['apellidos'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>

                                <span>
                                    <?= htmlspecialchars(
                                        $planeacion['numero_alumno'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>

                            <p class="planning-objective">
                                <?= htmlspecialchars(
                                    $planeacion['objetivo']
                                        ?: 'Sin objetivo registrado.',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <div class="planning-data">

                                <div>
                                    <span>Inicio</span>
                                    <strong>
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $planeacion['fecha_inicio']
                                            )
                                        ) ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Finalización</span>
                                    <strong>
                                        <?= $planeacion['fecha_fin']
                                            ? date(
                                                'd/m/Y',
                                                strtotime(
                                                    $planeacion['fecha_fin']
                                                )
                                            )
                                            : 'Sin definir' ?>
                                    </strong>
                                </div>

                                <div>
                                    <span>Entrenador</span>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $planeacion['entrenador'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>
    <?php require __DIR__ . '/../partials/mobile_nav.php'; ?>
</body>

</html>