<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requerirSesion();

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
    'activos',
    'inactivos',
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'todos';
}

/*
|--------------------------------------------------------------------------
| Resumen general
|--------------------------------------------------------------------------
*/

$consultaResumen = $pdo->query(
    'SELECT
        COUNT(*) AS total,
        SUM(
            CASE WHEN activo = 1 THEN 1 ELSE 0 END
        ) AS activos,
        SUM(
            CASE WHEN activo = 0 THEN 1 ELSE 0 END
        ) AS inactivos
    FROM ejercicios'
);

$resumen = $consultaResumen->fetch();

$totalEjercicios = (int) ($resumen['total'] ?? 0);
$totalActivos = (int) ($resumen['activos'] ?? 0);
$totalInactivos = (int) ($resumen['inactivos'] ?? 0);

/*
|--------------------------------------------------------------------------
| Construir consulta
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        ejercicios.id,
        ejercicios.nombre,
        ejercicios.categoria,
        ejercicios.tipo_medicion,
        ejercicios.descripcion,
        ejercicios.activo,
        ejercicios.creado_en,
        usuarios.nombre AS creado_por_nombre

    FROM ejercicios

    INNER JOIN usuarios
        ON usuarios.id = ejercicios.creado_por

    WHERE 1 = 1
';

$parametros = [];

if ($busqueda !== '') {
    $sql .= '
        AND (
            ejercicios.nombre LIKE :busqueda_nombre
            OR ejercicios.descripcion LIKE :busqueda_descripcion
            OR ejercicios.categoria LIKE :busqueda_categoria
        )
    ';

    $textoBusqueda = '%' . $busqueda . '%';

    $parametros['busqueda_nombre'] = $textoBusqueda;
    $parametros['busqueda_descripcion'] = $textoBusqueda;
    $parametros['busqueda_categoria'] = $textoBusqueda;
}

if ($estado === 'activos') {
    $sql .= '
        AND ejercicios.activo = 1
    ';
}

if ($estado === 'inactivos') {
    $sql .= '
        AND ejercicios.activo = 0
    ';
}

$sql .= '
    ORDER BY
        ejercicios.activo DESC,
        ejercicios.categoria ASC,
        ejercicios.nombre ASC
';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);

$ejercicios = $consulta->fetchAll();

$categorias = [
    'calentamiento' => 'Calentamiento',
    'cardio' => 'Cardio',
    'tecnica' => 'Técnica',
    'fuerza' => 'Fuerza',
    'costal' => 'Costal',
    'sombra' => 'Sombra',
    'manoplas' => 'Manoplas',
    'sparring' => 'Sparring',
    'abdomen' => 'Abdomen',
    'pierna' => 'Pierna',
    'otro' => 'Otro',
];

$tiposMedicion = [
    'tiempo' => 'Tiempo',
    'rounds' => 'Rounds',
    'series_repeticiones' => 'Series y repeticiones',
    'distancia' => 'Distancia',
    'libre' => 'Medición libre',
];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Ejercicios | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/alumnos.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/ejercicios.css">
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Catálogo de ejercicios</p>
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
                    <h2>Ejercicios registrados</h2>

                    <p>
                        Estos ejercicios podrán asignarse posteriormente
                        a las planeaciones de los alumnos.
                    </p>
                </div>

                <a
                    class="btn-primary"
                    href="<?= BASE_URL ?>/ejercicios/crear.php">
                    Registrar ejercicio
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

            <div class="exercise-summary">

                <article>
                    <span>Total</span>
                    <strong><?= $totalEjercicios ?></strong>
                </article>

                <article>
                    <span>Activos</span>
                    <strong><?= $totalActivos ?></strong>
                </article>

                <article>
                    <span>Inactivos</span>
                    <strong><?= $totalInactivos ?></strong>
                </article>

            </div>

            <form
                class="exercise-filters"
                method="GET"
                action="<?= BASE_URL ?>/ejercicios/listar.php">

                <input
                    class="form-control"
                    type="search"
                    name="q"
                    placeholder="Buscar ejercicio, categoría o descripción"
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
                        value="activos"
                        <?= $estado === 'activos'
                            ? 'selected'
                            : '' ?>>
                        Activos
                    </option>

                    <option
                        value="inactivos"
                        <?= $estado === 'inactivos'
                            ? 'selected'
                            : '' ?>>
                        Inactivos
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
                        href="<?= BASE_URL ?>/ejercicios/listar.php">
                        Limpiar
                    </a>

                <?php endif; ?>

            </form>

            <div class="result-summary">
                Resultados encontrados:
                <strong><?= count($ejercicios) ?></strong>
            </div>

            <?php if (empty($ejercicios)): ?>

                <div class="empty-state">

                    <h3>No se encontraron ejercicios</h3>

                    <p>
                        Registra un ejercicio o modifica los filtros.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>Ejercicio</th>
                                <th>Categoría</th>
                                <th>Medición</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Registró</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach (
                                $ejercicios as $ejercicio
                            ): ?>

                                <tr>

                                    <td data-label="Ejercicio">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $ejercicio['nombre'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>

                                    </td>

                                    <td data-label="Categoría">

                                        <span class="exercise-category">
                                            <?= htmlspecialchars(
                                                $categorias[$ejercicio['categoria']] ?? 'Otro',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </td>

                                    <td data-label="Medición">

                                        <span class="badge badge-neutral">
                                            <?= htmlspecialchars(
                                                $tiposMedicion[$ejercicio['tipo_medicion']] ?? 'Libre',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    </td>

                                    <td data-label="Descripción">

                                        <div class="exercise-description">
                                            <?= htmlspecialchars(
                                                $ejercicio['descripcion']
                                                    ?: 'Sin descripción',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </div>

                                    </td>

                                    <td data-label="Estado">

                                        <?php if (
                                            (int) $ejercicio['activo'] === 1
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

                                    <td data-label="Registró">

                                        <?= htmlspecialchars(
                                            $ejercicio['creado_por_nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <br>

                                        <small>
                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $ejercicio['creado_en']
                                                )
                                            ) ?>
                                        </small>

                                    </td>
                                    <td data-label="Acciones">

                                        <div class="exercise-actions">

                                            <a
                                                class="btn-small btn-edit"
                                                href="<?= BASE_URL ?>/ejercicios/editar.php?id=<?= (int) $ejercicio['id'] ?>">
                                                Editar
                                            </a>

                                            <form
                                                action="<?= BASE_URL ?>/api/ejercicios/cambiar_estado.php"
                                                method="POST"
                                                onsubmit="return confirm('¿Confirmas el cambio de estado de este ejercicio?');">

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
                                                    value="<?= (int) $ejercicio['id'] ?>">

                                                <button
                                                    class="btn-small <?= (int) $ejercicio['activo'] === 1
                                                                            ? 'btn-disable'
                                                                            : 'btn-enable' ?>"
                                                    type="submit">
                                                    <?= (int) $ejercicio['activo'] === 1
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

</body>

</html>