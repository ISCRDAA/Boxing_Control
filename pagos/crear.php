<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

/*
|--------------------------------------------------------------------------
| Consultar alumnos activos
|--------------------------------------------------------------------------
*/

$consultaAlumnos = $pdo->query(
    'SELECT
        id,
        numero_alumno,
        nombres,
        apellidos,
        tipo_pago,
        cuota
    FROM alumnos
    WHERE estado = "activo"
    ORDER BY apellidos ASC, nombres ASC'
);

$alumnos = $consultaAlumnos->fetchAll();

/*
|--------------------------------------------------------------------------
| Recuperar mensajes y datos anteriores
|--------------------------------------------------------------------------
*/

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_pago'] ?? [];

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_pago']
);

/*
|--------------------------------------------------------------------------
| Alumno recibido desde su expediente
|--------------------------------------------------------------------------
*/

$alumnoGet = filter_input(
    INPUT_GET,
    'alumno_id',
    FILTER_VALIDATE_INT
);

$alumnoSeleccionado = $datosAnteriores['alumno_id']
    ?? ($alumnoGet ?: '');

function valorPago(
    array $datos,
    string $campo,
    string $predeterminado = ''
): string {
    return htmlspecialchars(
        (string) ($datos[$campo] ?? $predeterminado),
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

    <title>Registrar pago | Gym Box</title>

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
        href="<?= BASE_URL ?>/css/pagos.css"
    >
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Registro de pagos</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/pagos/listar.php"
        >
            Volver a pagos
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">

                <div>
                    <h2>Registrar nuevo pago</h2>

                    <p>
                        Captura el pago recibido y la próxima fecha
                        de vencimiento.
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

            <?php if (empty($alumnos)): ?>

                <div class="alert alert-error">
                    No existen alumnos activos para registrar pagos.
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/alumnos/crear.php"
                >
                    Registrar alumno
                </a>

            <?php else: ?>

                <form
                    action="<?= BASE_URL ?>/api/pagos/guardar.php"
                    method="POST"
                    id="form-pago"
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

                    <div class="form-grid">

                        <div class="form-group form-group-full">

                            <label for="alumno_id">
                                Alumno *
                            </label>

                            <select
                                class="form-control"
                                id="alumno_id"
                                name="alumno_id"
                                required
                            >

                                <option value="">
                                    Selecciona un alumno
                                </option>

                                <?php foreach ($alumnos as $alumno): ?>

                                    <option
                                        value="<?= (int) $alumno['id'] ?>"
                                        data-cuota="<?= htmlspecialchars(
                                            (string) $alumno['cuota'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-tipo="<?= htmlspecialchars(
                                            $alumno['tipo_pago'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        <?= (string) $alumnoSeleccionado
                                            === (string) $alumno['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $alumno['numero_alumno']
                                            . ' - '
                                            . $alumno['nombres']
                                            . ' '
                                            . $alumno['apellidos'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div
                                class="student-payment-info"
                                id="student-payment-info"
                            >
                                Selecciona al alumno para consultar
                                su cuota.
                            </div>

                        </div>

                        <div class="form-group">

                            <label for="concepto">
                                Concepto *
                            </label>

                            <input
                                class="form-control"
                                type="text"
                                id="concepto"
                                name="concepto"
                                maxlength="100"
                                value="<?= valorPago(
                                    $datosAnteriores,
                                    'concepto',
                                    'Cuota de gimnasio'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="monto">
                                Cantidad recibida *
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                id="monto"
                                name="monto"
                                min="0.01"
                                step="0.01"
                                value="<?= valorPago(
                                    $datosAnteriores,
                                    'monto'
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="fecha_pago">
                                Fecha del pago *
                            </label>

                            <input
                                class="form-control"
                                type="date"
                                id="fecha_pago"
                                name="fecha_pago"
                                value="<?= valorPago(
                                    $datosAnteriores,
                                    'fecha_pago',
                                    date('Y-m-d')
                                ) ?>"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="metodo_pago">
                                Método de pago *
                            </label>

                            <?php
                            $metodoAnterior =
                                $datosAnteriores['metodo_pago']
                                ?? 'efectivo';
                            ?>

                            <select
                                class="form-control"
                                id="metodo_pago"
                                name="metodo_pago"
                                required
                            >

                                <option
                                    value="efectivo"
                                    <?= $metodoAnterior === 'efectivo'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Efectivo
                                </option>

                                <option
                                    value="transferencia"
                                    <?= $metodoAnterior === 'transferencia'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Transferencia
                                </option>

                            </select>

                        </div>

                        <div class="form-group form-group-full">

                            <label for="proximo_pago">
                                Próxima fecha de pago
                            </label>

                            <input
                                class="form-control"
                                type="date"
                                id="proximo_pago"
                                name="proximo_pago"
                                value="<?= valorPago(
                                    $datosAnteriores,
                                    'proximo_pago'
                                ) ?>"
                            >

                            <small class="field-help">
                                El sistema propondrá siete días o un mes,
                                según el tipo de pago del alumno.
                            </small>

                        </div>

                        <div class="form-group form-group-full">

                            <label for="observaciones">
                                Observaciones
                            </label>

                            <textarea
                                class="form-control"
                                id="observaciones"
                                name="observaciones"
                                rows="4"
                                maxlength="500"
                                placeholder="Información adicional del pago"
                            ><?= valorPago(
                                $datosAnteriores,
                                'observaciones'
                            ) ?></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            class="btn-secondary"
                            href="<?= BASE_URL ?>/pagos/listar.php"
                        >
                            Cancelar
                        </a>

                        <button
                            class="btn-primary"
                            type="submit"
                        >
                            Registrar pago
                        </button>

                    </div>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <script src="<?= BASE_URL ?>/js/pagos.js"></script>

</body>
</html>