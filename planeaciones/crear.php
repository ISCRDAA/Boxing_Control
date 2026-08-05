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
        nivel
    FROM alumnos
    WHERE estado = "activo"
    ORDER BY
        apellidos ASC,
        nombres ASC'
);

$alumnos = $consultaAlumnos->fetchAll();

/*
|--------------------------------------------------------------------------
| Recuperar mensajes y datos anteriores
|--------------------------------------------------------------------------
*/

$mensajeError = $_SESSION['mensaje_error'] ?? null;
$datosAnteriores = $_SESSION['datos_planeacion'] ?? [];

unset(
    $_SESSION['mensaje_error'],
    $_SESSION['datos_planeacion']
);

/*
|--------------------------------------------------------------------------
| Alumno recibido desde el expediente
|--------------------------------------------------------------------------
*/

$alumnoGet = filter_input(
    INPUT_GET,
    'alumno_id',
    FILTER_VALIDATE_INT
);

$alumnoSeleccionado = $datosAnteriores['alumno_id']
    ?? ($alumnoGet ?: '');

function valorPlaneacion(
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

$estadoSeleccionado =
    $datosAnteriores['estado'] ?? 'borrador';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nueva planeación | Gym Box</title>

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
            <p>Planeaciones personalizadas</p>
        </div>

        <a
            class="btn-secondary"
            href="<?= BASE_URL ?>/planeaciones/listar.php"
        >
            Volver a planeaciones
        </a>

    </header>

    <main class="module-container">

        <section class="form-card">

            <div class="module-header">

                <div>
                    <h2>Nueva planeación</h2>

                    <p>
                        Define la información general del entrenamiento
                        personalizado.
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
                    No existen alumnos activos para crear planeaciones.
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/alumnos/crear.php"
                >
                    Registrar alumno
                </a>

            <?php else: ?>

                <form
                    action="<?= BASE_URL ?>/api/planeaciones/guardar.php"
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
                                            . $alumno['apellidos']
                                            . ' - '
                                            . ucfirst($alumno['nivel']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

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
                                placeholder="Ejemplo: Resistencia básica - Semana 1"
                                value="<?= valorPlaneacion(
                                    $datosAnteriores,
                                    'nombre'
                                ) ?>"
                                required
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
                                value="<?= valorPlaneacion(
                                    $datosAnteriores,
                                    'fecha_inicio',
                                    date('Y-m-d')
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
                                value="<?= valorPlaneacion(
                                    $datosAnteriores,
                                    'fecha_fin'
                                ) ?>"
                            >

                            <small class="field-help">
                                Puede dejarse vacía si todavía no se conoce.
                            </small>

                        </div>

                        <div class="form-group form-group-full">

                            <label for="estado">
                                Estado inicial *
                            </label>

                            <select
                                class="form-control"
                                id="estado"
                                name="estado"
                                required
                            >

                                <option
                                    value="borrador"
                                    <?= $estadoSeleccionado === 'borrador'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Borrador
                                </option>

                                <option
                                    value="activa"
                                    <?= $estadoSeleccionado === 'activa'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Activa
                                </option>

                            </select>

                            <small class="field-help">
                                Utiliza borrador si todavía agregarás
                                o revisarás ejercicios.
                            </small>

                        </div>

                        <div class="form-group form-group-full">

                            <label for="objetivo">
                                Objetivo de la planeación
                            </label>

                            <textarea
                                class="form-control"
                                id="objetivo"
                                name="objetivo"
                                rows="4"
                                maxlength="500"
                                placeholder="Ejemplo: Mejorar resistencia cardiovascular y mantener técnica durante rounds prolongados."
                            ><?= valorPlaneacion(
                                $datosAnteriores,
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
                                placeholder="Indicaciones generales para el entrenador."
                            ><?= valorPlaneacion(
                                $datosAnteriores,
                                'observaciones'
                            ) ?></textarea>

                        </div>

                    </div>

                    <div class="form-actions">

                        <a
                            class="btn-secondary"
                            href="<?= BASE_URL ?>/planeaciones/listar.php"
                        >
                            Cancelar
                        </a>

                        <button
                            class="btn-primary"
                            type="submit"
                        >
                            Guardar planeación
                        </button>

                    </div>

                </form>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>