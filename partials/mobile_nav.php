<?php

$seccionActiva = $seccionActiva ?? '';

$itemsMovil = [
    [
        'id' => 'inicio',
        'texto' => 'Inicio',
        'url' => BASE_URL . '/dashboard.php',
        'icono' => '⌂',
    ],
    [
        'id' => 'alumnos',
        'texto' => 'Alumnos',
        'url' => BASE_URL . '/alumnos/listar.php',
        'icono' => '♙',
    ],
    [
        'id' => 'asistencias',
        'texto' => 'Asistencia',
        'url' => BASE_URL . '/asistencias/listar.php',
        'icono' => '✓',
    ],
    [
        'id' => 'pagos',
        'texto' => 'Pagos',
        'url' => BASE_URL . '/pagos/listar.php',
        'icono' => '$',
    ],
    [
        'id' => 'planeaciones',
        'texto' => 'Planes',
        'url' => BASE_URL . '/planeaciones/listar.php',
        'icono' => '≡',
    ],
];

?>

<nav class="mobile-bottom-nav">

    <?php foreach ($itemsMovil as $item): ?>

        <a
            href="<?= htmlspecialchars(
                $item['url'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="mobile-nav-item <?= $seccionActiva === $item['id']
                ? 'is-active'
                : '' ?>"
        >

            <span class="mobile-nav-icon">
                <?= htmlspecialchars(
                    $item['icono'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <span class="mobile-nav-text">
                <?= htmlspecialchars(
                    $item['texto'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </a>

    <?php endforeach; ?>

</nav>