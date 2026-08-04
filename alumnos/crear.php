<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';

requerirSesion();

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_formulario'] ?? [];

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_formulario']
);

function valorAnterior(
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

    <title>Registrar alumno | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css"
    >
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Registro de alumnos</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/alumnos/listar.php"
        >
            Volver a alumnos
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">
                <div>
                    <h2>Nuevo alumno</h2>
                    <p>
                        Registra los datos personales y administrativos.
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
                action="<?= BASE_URL ?>/api/alumnos/guardar.php"
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

                <div class="form-grid">

                    <div class="form-group">
                        <label for="nombres">
                            Nombres *
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="nombres"
                            name="nombres"
                            maxlength="100"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'nombres'
                            ) ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="apellidos">
                            Apellidos *
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            maxlength="120"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'apellidos'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento">
                            Fecha de nacimiento
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            id="fecha_nacimiento"
                            name="fecha_nacimiento"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'fecha_nacimiento'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="telefono">
                            Teléfono
                        </label>

                        <input
                            class="form-control"
                            type="tel"
                            id="telefono"
                            name="telefono"
                            maxlength="20"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'telefono'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="contacto_emergencia">
                            Contacto de emergencia
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="contacto_emergencia"
                            name="contacto_emergencia"
                            maxlength="150"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'contacto_emergencia'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="telefono_emergencia">
                            Teléfono de emergencia
                        </label>

                        <input
                            class="form-control"
                            type="tel"
                            id="telefono_emergencia"
                            name="telefono_emergencia"
                            maxlength="20"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'telefono_emergencia'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="fecha_ingreso">
                            Fecha de ingreso *
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            id="fecha_ingreso"
                            name="fecha_ingreso"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'fecha_ingreso',
                                date('Y-m-d')
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="tipo_pago">
                            Tipo de pago *
                        </label>

                        <?php
                        $tipoPagoAnterior =
                            $datosAnteriores['tipo_pago']
                            ?? 'mensual';
                        ?>

                        <select
                            class="form-control"
                            id="tipo_pago"
                            name="tipo_pago"
                            required
                        >
                            <option
                                value="semanal"
                                <?= $tipoPagoAnterior === 'semanal'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Semanal
                            </option>

                            <option
                                value="mensual"
                                <?= $tipoPagoAnterior === 'mensual'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Mensual
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cuota">
                            Cuota *
                        </label>

                        <input
                            class="form-control"
                            type="number"
                            id="cuota"
                            name="cuota"
                            min="0"
                            step="0.01"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'cuota',
                                '0.00'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="proximo_pago">
                            Próxima fecha de pago
                        </label>

                        <input
                            class="form-control"
                            type="date"
                            id="proximo_pago"
                            name="proximo_pago"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'proximo_pago'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="nivel">
                            Nivel del alumno *
                        </label>

                        <?php
                        $nivelAnterior =
                            $datosAnteriores['nivel']
                            ?? 'principiante';
                        ?>

                        <select
                            class="form-control"
                            id="nivel"
                            name="nivel"
                            required
                        >
                            <option
                                value="principiante"
                                <?= $nivelAnterior === 'principiante'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Principiante
                            </option>

                            <option
                                value="intermedio"
                                <?= $nivelAnterior === 'intermedio'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Intermedio
                            </option>

                            <option
                                value="avanzado"
                                <?= $nivelAnterior === 'avanzado'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Avanzado
                            </option>

                            <option
                                value="competidor"
                                <?= $nivelAnterior === 'competidor'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Competidor
                            </option>
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="objetivo">
                            Objetivo del alumno
                        </label>

                        <input
                            class="form-control"
                            type="text"
                            id="objetivo"
                            name="objetivo"
                            maxlength="255"
                            placeholder="Ejemplo: mejorar condición física"
                            value="<?= valorAnterior(
                                $datosAnteriores,
                                'objetivo'
                            ) ?>"
                        >
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
                            maxlength="2000"
                        ><?= valorAnterior(
                            $datosAnteriores,
                            'observaciones'
                        ) ?></textarea>
                    </div>

                </div>

                <div class="form-actions">

                    <a
                        class="btn-secondary"
                        href="<?= BASE_URL ?>/alumnos/listar.php"
                    >
                        Cancelar
                    </a>

                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Guardar alumno
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>