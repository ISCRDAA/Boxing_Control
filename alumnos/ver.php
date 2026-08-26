<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();
$seccionActiva = 'alumnos';

/*
|--------------------------------------------------------------------------
| Validar ID
|--------------------------------------------------------------------------
*/

$alumnoId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$alumnoId || $alumnoId < 1) {
    $_SESSION['mensaje_error'] = 'El alumno seleccionado no es válido.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Consultar alumno
|--------------------------------------------------------------------------
*/

$consulta = $pdo->prepare(
    'SELECT
        id,
        numero_alumno,
        nombres,
        apellidos,
        fecha_nacimiento,
        telefono,
        contacto_emergencia,
        telefono_emergencia,
        fecha_ingreso,
        tipo_pago,
        cuota,
        proximo_pago,
        nivel,
        objetivo,
        fotografia,
        observaciones,
        estado,
        creado_en,
        actualizado_en
    FROM alumnos
    WHERE id = :id
    LIMIT 1'
);

$consulta->execute([
    'id' => $alumnoId,
]);

$alumno = $consulta->fetch();

if (!$alumno) {
    $_SESSION['mensaje_error'] = 'El alumno solicitado no existe.';

    header('Location: ' . BASE_URL . '/alumnos/listar.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Resumen e historial de pagos
|--------------------------------------------------------------------------
*/

$consultaResumenPagos = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_pagos,
        COALESCE(SUM(monto), 0) AS total_pagado
    FROM pagos
    WHERE alumno_id = :alumno_id'
);

$consultaResumenPagos->execute([
    'alumno_id' => $alumnoId,
]);

$resumenPagos = $consultaResumenPagos->fetch();

$consultaPagos = $pdo->prepare(
    'SELECT
        pagos.concepto,
        pagos.monto,
        pagos.fecha_pago,
        pagos.metodo_pago,
        pagos.proximo_pago,
        usuarios.nombre AS recibido_por
    FROM pagos
    INNER JOIN usuarios
        ON usuarios.id = pagos.usuario_id
    WHERE pagos.alumno_id = :alumno_id
    ORDER BY
        pagos.fecha_pago DESC,
        pagos.id DESC
    LIMIT 10'
);

$consultaPagos->execute([
    'alumno_id' => $alumnoId,
]);

$pagosAlumno = $consultaPagos->fetchAll();

/*
|--------------------------------------------------------------------------
| Resumen e historial de asistencias
|--------------------------------------------------------------------------
*/

$consultaResumenAsistencias = $pdo->prepare(
    'SELECT
        COUNT(*) AS total_asistencias,
        MAX(fecha) AS ultima_asistencia
    FROM asistencias
    WHERE alumno_id = :alumno_id'
);

$consultaResumenAsistencias->execute([
    'alumno_id' => $alumnoId,
]);

$resumenAsistencias = $consultaResumenAsistencias->fetch();

$consultaAsistencias = $pdo->prepare(
    'SELECT
        asistencias.fecha,
        asistencias.hora_llegada,
        usuarios.nombre AS registrado_por
    FROM asistencias
    INNER JOIN usuarios
        ON usuarios.id = asistencias.usuario_id
    WHERE asistencias.alumno_id = :alumno_id
    ORDER BY
        asistencias.fecha DESC,
        asistencias.hora_llegada DESC
    LIMIT 10'
);

$consultaAsistencias->execute([
    'alumno_id' => $alumnoId,
]);

$asistenciasAlumno = $consultaAsistencias->fetchAll();

/*
|--------------------------------------------------------------------------
| Comprobar asistencia del día
|--------------------------------------------------------------------------
*/

$consultaAsistenciaHoy = $pdo->prepare(
    'SELECT
        id,
        hora_llegada
    FROM asistencias
    WHERE alumno_id = :alumno_id
        AND fecha = :fecha
    LIMIT 1'
);

$consultaAsistenciaHoy->execute([
    'alumno_id' => $alumnoId,
    'fecha' => date('Y-m-d'),
]);

$asistenciaHoy = $consultaAsistenciaHoy->fetch();

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

/*
|--------------------------------------------------------------------------
| Calcular edad
|--------------------------------------------------------------------------
*/

$edad = null;

if (!empty($alumno['fecha_nacimiento'])) {
    $fechaNacimiento = new DateTime($alumno['fecha_nacimiento']);
    $hoy = new DateTime();

    $edad = $fechaNacimiento->diff($hoy)->y;
}

/*
|--------------------------------------------------------------------------
| Estado del pago
|--------------------------------------------------------------------------
*/

$estadoPago = 'Sin fecha de vencimiento';
$clasePago = 'badge-neutral';

if (!empty($alumno['proximo_pago'])) {
    $hoyTexto = date('Y-m-d');

    if ($alumno['proximo_pago'] < $hoyTexto) {
        $estadoPago = 'Pago vencido';
        $clasePago = 'badge-danger';
    } elseif ($alumno['proximo_pago'] === $hoyTexto) {
        $estadoPago = 'Vence hoy';
        $clasePago = 'badge-warning';
    } else {
        $estadoPago = 'Pago vigente';
        $clasePago = 'badge-success';
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Expediente de alumno | Gym Box
    </title>

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
            <p>Expediente del alumno</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/alumnos/listar.php">
            Volver a alumnos
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

        <section class="profile-card">

            <div class="profile-header">

                <div class="profile-identity">

                    <div class="profile-avatar">
                        <?= htmlspecialchars(
                            strtoupper(
                                mb_substr($alumno['nombres'], 0, 1)
                                    . mb_substr($alumno['apellidos'], 0, 1)
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div>
                        <p class="profile-number">
                            <?= htmlspecialchars(
                                $alumno['numero_alumno'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <h2>
                            <?= htmlspecialchars(
                                $alumno['nombres']
                                    . ' '
                                    . $alumno['apellidos'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <div class="profile-badges">

                            <span class="badge badge-neutral">
                                <?= htmlspecialchars(
                                    ucfirst($alumno['nivel']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <?php if ($alumno['estado'] === 'activo'): ?>

                                <span class="badge badge-success">
                                    Activo
                                </span>

                            <?php else: ?>

                                <span class="badge badge-danger">
                                    Inactivo
                                </span>

                            <?php endif; ?>

                            <span class="badge <?= $clasePago ?>">
                                <?= $estadoPago ?>
                            </span>

                        </div>
                    </div>

                </div>

                <div class="profile-actions">


                    <?php if ($alumno['estado'] === 'activo'): ?>

                        <a
                            class="btn-success"
                            href="<?= BASE_URL ?>/pagos/crear.php?alumno_id=<?= (int) $alumno['id'] ?>">
                            Registrar pago
                        </a>

                    <?php endif; ?>

                    <a
                        class="btn-primary"
                        href="<?= BASE_URL ?>/alumnos/editar.php?id=<?= $alumno['id'] ?>">
                        Editar alumno
                    </a>
                    <?php if (
                        $alumno['estado'] === 'activo'
                        && !$asistenciaHoy
                    ): ?>

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
                                value="perfil">

                            <button
                                class="btn-attendance-profile"
                                type="submit">
                                Registrar asistencia
                            </button>

                        </form>

                    <?php elseif ($asistenciaHoy): ?>

                        <span class="attendance-profile-status">
                            Presente hoy a las
                            <?= date(
                                'H:i',
                                strtotime($asistenciaHoy['hora_llegada'])
                            ) ?>
                        </span>

                    <?php endif; ?>
                    <?php if ($alumno['estado'] === 'activo'): ?>

                        <a
                            class="btn-primary"
                            href="<?= BASE_URL ?>/planeaciones/crear.php?alumno_id=<?= (int) $alumno['id'] ?>">
                            Nueva planeación
                        </a>

                    <?php endif; ?>



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
                            value="ver">

                        <button
                            class="<?= $alumno['estado'] === 'activo'
                                        ? 'btn-danger'
                                        : 'btn-success' ?>"
                            type="submit">
                            <?= $alumno['estado'] === 'activo'
                                ? 'Desactivar'
                                : 'Activar' ?>
                        </button>

                    </form>

                </div>

            </div>

        </section>

        <section class="detail-grid">

            <article class="detail-card">

                <h3>Datos personales</h3>

                <dl class="detail-list">

                    <div>
                        <dt>Fecha de nacimiento</dt>
                        <dd>
                            <?= $alumno['fecha_nacimiento']
                                ? date(
                                    'd/m/Y',
                                    strtotime($alumno['fecha_nacimiento'])
                                )
                                : 'No registrada' ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Edad</dt>
                        <dd>
                            <?= $edad !== null
                                ? $edad . ' años'
                                : 'No disponible' ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Teléfono</dt>
                        <dd>
                            <?= htmlspecialchars(
                                $alumno['telefono']
                                    ?: 'No registrado',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Fecha de ingreso</dt>
                        <dd>
                            <?= date(
                                'd/m/Y',
                                strtotime($alumno['fecha_ingreso'])
                            ) ?>
                        </dd>
                    </div>

                </dl>

            </article>

            <article class="detail-card">

                <h3>Contacto de emergencia</h3>

                <dl class="detail-list">

                    <div>
                        <dt>Nombre</dt>
                        <dd>
                            <?= htmlspecialchars(
                                $alumno['contacto_emergencia']
                                    ?: 'No registrado',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Teléfono</dt>
                        <dd>
                            <?= htmlspecialchars(
                                $alumno['telefono_emergencia']
                                    ?: 'No registrado',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                </dl>

            </article>

            <article class="detail-card">

                <h3>Información de pago</h3>

                <dl class="detail-list">

                    <div>
                        <dt>Tipo de pago</dt>
                        <dd>
                            <?= htmlspecialchars(
                                ucfirst($alumno['tipo_pago']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Cuota</dt>
                        <dd>
                            $<?= number_format(
                                    (float) $alumno['cuota'],
                                    2
                                ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Próximo pago</dt>
                        <dd>
                            <?= $alumno['proximo_pago']
                                ? date(
                                    'd/m/Y',
                                    strtotime($alumno['proximo_pago'])
                                )
                                : 'No registrado' ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Situación</dt>
                        <dd>
                            <span class="badge <?= $clasePago ?>">
                                <?= $estadoPago ?>
                            </span>
                        </dd>
                    </div>

                </dl>

            </article>

            <article class="detail-card">

                <h3>Información deportiva</h3>

                <dl class="detail-list">

                    <div>
                        <dt>Nivel</dt>
                        <dd>
                            <?= htmlspecialchars(
                                ucfirst($alumno['nivel']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                    <div>
                        <dt>Objetivo</dt>
                        <dd>
                            <?= htmlspecialchars(
                                $alumno['objetivo']
                                    ?: 'No registrado',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </dd>
                    </div>

                </dl>

            </article>

            <article class="detail-card detail-card-full">

                <h3>Observaciones</h3>

                <p class="detail-text">
                    <?= nl2br(
                        htmlspecialchars(
                            $alumno['observaciones']
                                ?: 'No existen observaciones.',
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>
                </p>

            </article>

        </section>
        <section class="history-grid">

            <article class="history-card">

                <div class="history-card-header">

                    <div>
                        <br>
                        <h3>Historial de pagos</h3>

                        <p>
                            <?= (int) $resumenPagos['total_pagos'] ?>
                            pagos registrados
                        </p>
                    </div>

                    <strong class="history-total">
                        $<?= number_format(
                                (float) $resumenPagos['total_pagado'],
                                2
                            ) ?>
                    </strong>

                </div>

                <?php if (empty($pagosAlumno)): ?>

                    <div class="empty-history">
                        No existen pagos registrados.
                    </div>

                <?php else: ?>

                    <div class="table-container">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Cantidad</th>
                                    <th>Método</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($pagosAlumno as $pago): ?>

                                    <tr>

                                        <td data-label="Fecha">
                                            <?= date(
                                                'd/m/Y',
                                                strtotime($pago['fecha_pago'])
                                            ) ?>
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
                                                ucfirst($pago['metodo_pago']),
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

            </article>

            <article class="history-card">

                <div class="history-card-header">

                    <div>
                        <h3>Asistencias recientes</h3>

                        <p>
                            <?= (int) $resumenAsistencias['total_asistencias'] ?>
                            asistencias registradas
                        </p>
                    </div>

                </div>

                <?php if (empty($asistenciasAlumno)): ?>

                    <div class="empty-history">
                        No existen asistencias registradas.
                    </div>

                <?php else: ?>

                    <div class="table-container">

                        <table class="data-table">

                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Registró</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach (
                                    $asistenciasAlumno as $asistencia
                                ): ?>

                                    <tr>

                                        <td data-label="Fecha">
                                            <?= date(
                                                'd/m/Y',
                                                strtotime($asistencia['fecha'])
                                            ) ?>
                                        </td>

                                        <td data-label="Hora">
                                            <?= date(
                                                'H:i',
                                                strtotime(
                                                    $asistencia['hora_llegada']
                                                )
                                            ) ?>
                                        </td>

                                        <td data-label="Registró">
                                            <?= htmlspecialchars(
                                                $asistencia['registrado_por'],
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

            </article>

        </section>

    </main>
    <?php require __DIR__ . '/../partials/mobile_nav.php'; ?>
</body>

</html>