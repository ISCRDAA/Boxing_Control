<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();
$seccionActiva = 'asistencias';

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

$fechaHoy = date('Y-m-d');
$busqueda = trim((string) ($_GET['q'] ?? ''));

/*
|--------------------------------------------------------------------------
| Resumen general del día
|--------------------------------------------------------------------------
*/

$consultaResumen = $pdo->prepare(
    'SELECT
        COUNT(alumnos.id) AS total_activos,
        SUM(
            CASE
                WHEN asistencias.id IS NOT NULL THEN 1
                ELSE 0
            END
        ) AS total_presentes
    FROM alumnos
    LEFT JOIN asistencias
        ON asistencias.alumno_id = alumnos.id
        AND asistencias.fecha = :fecha
    WHERE alumnos.estado = "activo"'
);

$consultaResumen->execute([
    'fecha' => $fechaHoy,
]);

$resumen = $consultaResumen->fetch();

$totalActivos = (int) ($resumen['total_activos'] ?? 0);
$totalPresentes = (int) ($resumen['total_presentes'] ?? 0);
$totalPendientes = max(0, $totalActivos - $totalPresentes);

/*
|--------------------------------------------------------------------------
| Lista de alumnos activos
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        alumnos.id,
        alumnos.numero_alumno,
        alumnos.nombres,
        alumnos.apellidos,
        alumnos.nivel,
        alumnos.proximo_pago,

        asistencias.id AS asistencia_id,
        asistencias.hora_llegada

    FROM alumnos

    LEFT JOIN asistencias
        ON asistencias.alumno_id = alumnos.id
        AND asistencias.fecha = :fecha

    WHERE alumnos.estado = "activo"
';

$parametros = [
    'fecha' => $fechaHoy,
];

if ($busqueda !== '') {
    $sql .= '
        AND (
            alumnos.numero_alumno LIKE :busqueda_numero
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

    $parametros['busqueda_numero'] = $textoBusqueda;
    $parametros['busqueda_nombres'] = $textoBusqueda;
    $parametros['busqueda_apellidos'] = $textoBusqueda;
    $parametros['busqueda_completa'] = $textoBusqueda;
}

$sql .= '
    ORDER BY
        CASE
            WHEN asistencias.id IS NULL THEN 0
            ELSE 1
        END ASC,
        alumnos.apellidos ASC,
        alumnos.nombres ASC
';

$consultaAlumnos = $pdo->prepare($sql);
$consultaAlumnos->execute($parametros);

$alumnos = $consultaAlumnos->fetchAll();

/*
|--------------------------------------------------------------------------
| Últimos registros de asistencia
|--------------------------------------------------------------------------
*/

$consultaHistorial = $pdo->query(
    'SELECT
        asistencias.fecha,
        asistencias.hora_llegada,

        alumnos.id AS alumno_id,
        alumnos.numero_alumno,
        alumnos.nombres,
        alumnos.apellidos,

        usuarios.nombre AS registrado_por

    FROM asistencias

    INNER JOIN alumnos
        ON alumnos.id = asistencias.alumno_id

    INNER JOIN usuarios
        ON usuarios.id = asistencias.usuario_id

    ORDER BY
        asistencias.fecha DESC,
        asistencias.hora_llegada DESC,
        asistencias.id DESC

    LIMIT 15'
);

$historial = $consultaHistorial->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Asistencias | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/asistencias.css">
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/mobile.css">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>

            <p>
                Asistencia del
                <?= date('d/m/Y') ?>
            </p>
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
                    <h2>Registrar llegadas</h2>

                    <p>
                        Pulsa el botón cuando llegue cada alumno.
                    </p>
                </div>

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

            <div class="attendance-summary">

                <article>
                    <span>Alumnos activos</span>
                    <strong><?= $totalActivos ?></strong>
                </article>

                <article>
                    <span>Presentes hoy</span>
                    <strong><?= $totalPresentes ?></strong>
                </article>

                <article>
                    <span>Pendientes</span>
                    <strong><?= $totalPendientes ?></strong>
                </article>

            </div>

            <form
                class="search-form"
                method="GET"
                action="<?= BASE_URL ?>/asistencias/listar.php">

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    placeholder="Buscar alumno"
                    value="<?= htmlspecialchars(
                                $busqueda,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                <button
                    class="btn-primary"
                    type="submit">
                    Buscar
                </button>

                <?php if ($busqueda !== ''): ?>

                    <a
                        class="btn-secondary"
                        href="<?= BASE_URL ?>/asistencias/listar.php">
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <?php if (empty($alumnos)): ?>

                <div class="empty-state">

                    <h3>No se encontraron alumnos activos</h3>

                    <p>
                        Registra un alumno o modifica la búsqueda.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Alumno</th>
                                <th>Nivel</th>
                                <th>Estado de hoy</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($alumnos as $alumno): ?>

                                <tr>

                                    <td data-label="Número">
                                        <strong>
                                            <?= htmlspecialchars(
                                                $alumno['numero_alumno'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td data-label="Alumno">

                                        <a
                                            class="student-link"
                                            href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $alumno['id'] ?>">
                                            <?= htmlspecialchars(
                                                $alumno['nombres']
                                                    . ' '
                                                    . $alumno['apellidos'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </a>

                                    </td>

                                    <td data-label="Nivel">
                                        <span class="badge badge-neutral">
                                            <?= htmlspecialchars(
                                                ucfirst($alumno['nivel']),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td data-label="Estado de hoy">

                                        <?php if ($alumno['asistencia_id']): ?>

                                            <span class="badge badge-success">
                                                Presente
                                            </span>

                                            <div class="attendance-time">
                                                <?= date(
                                                    'H:i',
                                                    strtotime(
                                                        $alumno['hora_llegada']
                                                    )
                                                ) ?>
                                                horas
                                            </div>

                                        <?php else: ?>

                                            <span class="badge badge-warning">
                                                Pendiente
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td data-label="Acción">

                                        <?php if (!$alumno['asistencia_id']): ?>

                                            <form
                                                action="<?= BASE_URL ?>/api/asistencias/registrar.php"
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
                                                    name="alumno_id"
                                                    value="<?= (int) $alumno['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="origen"
                                                    value="listar">

                                                <button
                                                    class="btn-attendance"
                                                    type="submit">
                                                    Registrar llegada
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="attendance-complete">
                                                Asistencia registrada
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

        <section class="module-card history-section">

            <div class="module-header">

                <div>
                    <h2>Últimas asistencias</h2>

                    <p>
                        Registros recientes del gimnasio.
                    </p>
                </div>

            </div>

            <?php if (empty($historial)): ?>

                <div class="empty-state">

                    <h3>No existen asistencias</h3>

                    <p>
                        Los registros aparecerán en esta sección.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Alumno</th>
                                <th>Registró</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($historial as $registro): ?>

                                <tr>

                                    <td data-label="Fecha">
                                        <?= date(
                                            'd/m/Y',
                                            strtotime($registro['fecha'])
                                        ) ?>
                                    </td>

                                    <td data-label="Hora">
                                        <?= date(
                                            'H:i',
                                            strtotime(
                                                $registro['hora_llegada']
                                            )
                                        ) ?>
                                    </td>

                                    <td data-label="Alumno">

                                        <a
                                            class="student-link"
                                            href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $registro['alumno_id'] ?>">
                                            <?= htmlspecialchars(
                                                $registro['nombres']
                                                    . ' '
                                                    . $registro['apellidos'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </a>

                                        <br>

                                        <small>
                                            <?= htmlspecialchars(
                                                $registro['numero_alumno'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>

                                    </td>

                                    <td data-label="Registró">
                                        <?= htmlspecialchars(
                                            $registro['registrado_por'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>
    <?php require __DIR__ . '/../partials/mobile_nav.php'; ?>
</body>

</html>