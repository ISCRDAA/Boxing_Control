<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();
$seccionActiva = 'alumnos';

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

$busqueda = trim((string) ($_GET['q'] ?? ''));

$sql = '
    SELECT
        id,
        numero_alumno,
        nombres,
        apellidos,
        telefono,
        fecha_ingreso,
        tipo_pago,
        cuota,
        proximo_pago,
        nivel,
        estado
    FROM alumnos
';

$parametros = [];

if ($busqueda !== '') {

    $sql .= '
        WHERE
            numero_alumno LIKE :busqueda_numero
            OR nombres LIKE :busqueda_nombres
            OR apellidos LIKE :busqueda_apellidos
            OR CONCAT(
                nombres,
                " ",
                apellidos
            ) LIKE :busqueda_completa
            OR telefono LIKE :busqueda_telefono
    ';

    $textoBusqueda = '%' . $busqueda . '%';

    $parametros = [
        'busqueda_numero' => $textoBusqueda,
        'busqueda_nombres' => $textoBusqueda,
        'busqueda_apellidos' => $textoBusqueda,
        'busqueda_completa' => $textoBusqueda,
        'busqueda_telefono' => $textoBusqueda,
    ];
}

$sql .= '
    ORDER BY
        estado ASC,
        apellidos ASC,
        nombres ASC
';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$alumnos = $consulta->fetchAll();

$hoy = date('Y-m-d');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Alumnos | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/mobile.css">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Control de alumnos</p>
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
                    <h2>Alumnos registrados</h2>

                    <p>
                        Consulta y administra los alumnos del gimnasio.
                    </p>
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/alumnos/crear.php">
                    Registrar alumno
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

            <form
                class="search-form"
                method="GET"
                action="<?= BASE_URL ?>/alumnos/listar.php">

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    placeholder="Buscar por nombre, número o teléfono"
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
                        href="<?= BASE_URL ?>/alumnos/listar.php">
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <div class="result-summary">
                Total de resultados:
                <strong><?= count($alumnos) ?></strong>
            </div>

            <?php if (empty($alumnos)): ?>

                <div class="empty-state">

                    <h3>No se encontraron alumnos</h3>

                    <p>
                        Registra al primer alumno o modifica la búsqueda.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Alumno</th>
                                <th>Teléfono</th>
                                <th>Nivel</th>
                                <th>Pago</th>
                                <th>Próximo pago</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($alumnos as $alumno): ?>

                                <?php
                                $estadoPago = 'Sin fecha';
                                $clasePago = 'badge-neutral';

                                if (!empty($alumno['proximo_pago'])) {
                                    if (
                                        $alumno['proximo_pago'] < $hoy
                                    ) {
                                        $estadoPago = 'Vencido';
                                        $clasePago = 'badge-danger';
                                    } elseif (
                                        $alumno['proximo_pago'] === $hoy
                                    ) {
                                        $estadoPago = 'Vence hoy';
                                        $clasePago = 'badge-warning';
                                    } else {
                                        $estadoPago = 'Vigente';
                                        $clasePago = 'badge-success';
                                    }
                                }
                                ?>

                                <tr>

                                    <td data-label="Número">
                                        <strong>
                                            <?= htmlspecialchars(
                                                (string) $alumno['numero_alumno'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td data-label="Alumno">
                                        <?= htmlspecialchars(
                                            $alumno['nombres']
                                                . ' '
                                                . $alumno['apellidos'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td data-label="Teléfono">
                                        <?= htmlspecialchars(
                                            $alumno['telefono']
                                                ?: 'Sin teléfono',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td data-label="Nivel">
                                        <span class="badge badge-neutral">
                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $alumno['nivel']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    </td>

                                    <td data-label="Pago">
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $alumno['tipo_pago']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <br>

                                        <small>
                                            $<?= number_format(
                                                    (float) $alumno['cuota'],
                                                    2
                                                ) ?>
                                        </small>
                                    </td>

                                    <td data-label="Próximo pago">

                                        <?php if ($alumno['proximo_pago']): ?>

                                            <?= htmlspecialchars(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $alumno['proximo_pago']
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        <?php else: ?>

                                            Sin fecha

                                        <?php endif; ?>

                                        <br>

                                        <span
                                            class="badge <?= $clasePago ?>">
                                            <?= $estadoPago ?>
                                        </span>
                                    </td>

                                    <td data-label="Estado">

                                        <?php if (
                                            $alumno['estado'] === 'activo'
                                        ): ?>

                                            <span
                                                class="badge badge-success">
                                                Activo
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge badge-danger">
                                                Inactivo
                                            </span>

                                        <?php endif; ?>

                                    </td>
                                    <td data-label="Acciones">

                                        <div class="table-actions">

                                            <a
                                                class="btn-small btn-view"
                                                href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $alumno['id'] ?>">
                                                Ver
                                            </a>

                                            <a
                                                class="btn-small btn-edit"
                                                href="<?= BASE_URL ?>/alumnos/editar.php?id=<?= (int) $alumno['id'] ?>">
                                                Editar
                                            </a>

                                            <form
                                                action="<?= BASE_URL ?>/api/alumnos/cambiar_estado.php"
                                                method="POST"
                                                onsubmit="return confirm('¿Confirmas el cambio de estado del alumno?');">

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
                                                    value="<?= (int) $alumno['id'] ?>">

                                                <input
                                                    type="hidden"
                                                    name="origen"
                                                    value="listar">

                                                <button
                                                    class="btn-small <?= $alumno['estado'] === 'activo'
                                                                            ? 'btn-disable'
                                                                            : 'btn-enable' ?>"
                                                    type="submit">
                                                    <?= $alumno['estado'] === 'activo'
                                                        ? 'Desactivar'
                                                        : 'Activar' ?>
                                                </button>

                                            </form>

                                        </div>

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