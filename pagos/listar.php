<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();
$seccionActiva = 'pagos';

$mensajeExito = $_SESSION['mensaje_exito'] ?? null;
$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset(
    $_SESSION['mensaje_exito'],
    $_SESSION['mensaje_error']
);

$busqueda = trim(
    (string) ($_GET['q'] ?? '')
);

$sql = '
    SELECT
        pagos.id,
        pagos.concepto,
        pagos.monto,
        pagos.fecha_pago,
        pagos.metodo_pago,
        pagos.proximo_pago,
        pagos.observaciones,

        alumnos.id AS alumno_id,
        alumnos.numero_alumno,
        alumnos.nombres,
        alumnos.apellidos,

        usuarios.nombre AS recibido_por
    FROM pagos
    INNER JOIN alumnos
        ON alumnos.id = pagos.alumno_id
    INNER JOIN usuarios
        ON usuarios.id = pagos.usuario_id
';

$parametros = [];

if ($busqueda !== '') {

    $sql .= '
        WHERE
            alumnos.numero_alumno LIKE :busqueda_numero
            OR alumnos.nombres LIKE :busqueda_nombres
            OR alumnos.apellidos LIKE :busqueda_apellidos
            OR CONCAT(
                alumnos.nombres,
                " ",
                alumnos.apellidos
            ) LIKE :busqueda_completa
            OR pagos.concepto LIKE :busqueda_concepto
    ';

    $textoBusqueda = '%' . $busqueda . '%';

    $parametros = [
        'busqueda_numero' => $textoBusqueda,
        'busqueda_nombres' => $textoBusqueda,
        'busqueda_apellidos' => $textoBusqueda,
        'busqueda_completa' => $textoBusqueda,
        'busqueda_concepto' => $textoBusqueda,
    ];
}

$sql .= '
    ORDER BY
        pagos.fecha_pago DESC,
        pagos.id DESC
';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$pagos = $consulta->fetchAll();

$totalMostrado = 0.00;

foreach ($pagos as $pago) {
    $totalMostrado += (float) $pago['monto'];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Historial de pagos | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/pagos.css">
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/mobile.css">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Historial de pagos</p>
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
                    <h2>Pagos registrados</h2>

                    <p>
                        Consulta los cobros realizados a los alumnos.
                    </p>
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/pagos/crear.php">
                    Registrar pago
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
                action="<?= BASE_URL ?>/pagos/listar.php">

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    placeholder="Buscar alumno o concepto"
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
                        href="<?= BASE_URL ?>/pagos/listar.php">
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <div class="payment-summary">

                <div>
                    <span>Pagos mostrados</span>
                    <strong><?= count($pagos) ?></strong>
                </div>

                <div>
                    <span>Total mostrado</span>
                    <strong>
                        $<?= number_format(
                                $totalMostrado,
                                2
                            ) ?>
                    </strong>
                </div>

            </div>

            <?php if (empty($pagos)): ?>

                <div class="empty-state">

                    <h3>No existen pagos registrados</h3>

                    <p>
                        Registra el primer pago para comenzar
                        el historial.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Alumno</th>
                                <th>Concepto</th>
                                <th>Cantidad</th>
                                <th>Método</th>
                                <th>Próximo pago</th>
                                <th>Recibió</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($pagos as $pago): ?>

                                <tr>

                                    <td data-label="Fecha">
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $pago['fecha_pago']
                                            )
                                        ) ?>
                                    </td>

                                    <td data-label="Alumno">

                                        <a
                                            class="student-link"
                                            href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= (int) $pago['alumno_id'] ?>">
                                            <?= htmlspecialchars(
                                                $pago['nombres']
                                                    . ' '
                                                    . $pago['apellidos'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </a>

                                        <br>

                                        <small>
                                            <?= htmlspecialchars(
                                                $pago['numero_alumno'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>

                                    </td>

                                    <td data-label="Concepto">
                                        <?= htmlspecialchars(
                                            $pago['concepto'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td data-label="Cantidad">
                                        <strong>
                                            $<?= number_format(
                                                    (float) $pago['monto'],
                                                    2
                                                ) ?>
                                        </strong>
                                    </td>

                                    <td data-label="Método">
                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $pago['metodo_pago']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td data-label="Próximo pago">

                                        <?php if ($pago['proximo_pago']): ?>

                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $pago['proximo_pago']
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            Sin fecha

                                        <?php endif; ?>

                                    </td>

                                    <td data-label="Recibió">
                                        <?= htmlspecialchars(
                                            $pago['recibido_por'],
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