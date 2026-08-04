<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

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

$consulta = $pdo->prepare(
    'SELECT *
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

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosEdicion = $_SESSION['datos_edicion'] ?? null;

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_edicion']
);

/*
|--------------------------------------------------------------------------
| Usar datos anteriores solamente si pertenecen al mismo alumno
|--------------------------------------------------------------------------
*/

if (
    is_array($datosEdicion)
    && isset($datosEdicion['id'])
    && (int) $datosEdicion['id'] === $alumnoId
) {
    $datos = array_merge($alumno, $datosEdicion);
} else {
    $datos = $alumno;
}

function valorEdicion(
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

    <title>Editar alumno | Gym Box</title>

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
            <p>Edición de alumno</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= $alumnoId ?>"
        >
            Volver al expediente
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">

                <div>
                    <h2>Editar alumno</h2>

                    <p>
                        Número:
                        <strong>
                            <?= htmlspecialchars(
                                $alumno['numero_alumno'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
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
                action="<?= BASE_URL ?>/api/alumnos/actualizar.php"
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
                    value="<?= $alumnoId ?>"
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
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
                            value="<?= valorEdicion(
                                $datos,
                                'fecha_ingreso'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="tipo_pago">
                            Tipo de pago *
                        </label>

                        <select
                            class="form-control"
                            id="tipo_pago"
                            name="tipo_pago"
                            required
                        >
                            <option
                                value="semanal"
                                <?= $datos['tipo_pago'] === 'semanal'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Semanal
                            </option>

                            <option
                                value="mensual"
                                <?= $datos['tipo_pago'] === 'mensual'
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
                            value="<?= valorEdicion(
                                $datos,
                                'cuota'
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
                            value="<?= valorEdicion(
                                $datos,
                                'proximo_pago'
                            ) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="nivel">
                            Nivel *
                        </label>

                        <select
                            class="form-control"
                            id="nivel"
                            name="nivel"
                            required
                        >
                            <?php
                            $niveles = [
                                'principiante' => 'Principiante',
                                'intermedio' => 'Intermedio',
                                'avanzado' => 'Avanzado',
                                'competidor' => 'Competidor',
                            ];
                            ?>

                            <?php foreach ($niveles as $valor => $texto): ?>

                                <option
                                    value="<?= $valor ?>"
                                    <?= $datos['nivel'] === $valor
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= $texto ?>
                                </option>

                            <?php endforeach; ?>

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
                            value="<?= valorEdicion(
                                $datos,
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
                            rows="5"
                            maxlength="2000"
                        ><?= valorEdicion(
                            $datos,
                            'observaciones'
                        ) ?></textarea>
                    </div>

                </div>

                <div class="form-actions">

                    <a
                        class="btn-secondary"
                        href="<?= BASE_URL ?>/alumnos/ver.php?id=<?= $alumnoId ?>"
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