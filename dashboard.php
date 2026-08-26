<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

requerirSesion();
$seccionActiva = 'inicio';

$usuario = usuarioActual();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Panel principal | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/dashboard.css">
    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/mobile.css">

        <?php require __DIR__ . '/partials/pwa_head.php'; ?>
</head>

<body>

    <header class="topbar">

        <div class="brand">
            <h1>Gym Box</h1>
            <p>Panel de control</p>
        </div>

        <div class="user-area">

            <div class="user-info">
                <strong>
                    <?= htmlspecialchars(
                        $usuario['nombre'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $usuario['rol'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>
            </div>

            <form
                action="<?= BASE_URL ?>/auth/logout.php"
                method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                                $_SESSION['csrf_token'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                <button
                    class="btn-logout"
                    type="submit">
                    Cerrar sesión
                </button>

            </form>

        </div>

    </header>

    <main class="main-content">

        <section class="welcome-card">

            <h2>
                Bienvenido,
                <?= htmlspecialchars(
                    $usuario['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h2>

            <p>
                Desde este panel podrás administrar alumnos,
                pagos, asistencias y planeaciones.
            </p>

            <section class="mobile-app-menu">

                <a
                    class="app-menu-card"
                    href="<?= BASE_URL ?>/alumnos/listar.php">
                    <span class="app-menu-icon">
                        ♙
                    </span>

                    <div>
                        <strong>Alumnos</strong>
                        <span>Expedientes y datos</span>
                    </div>
                </a>

                <a
                    class="app-menu-card"
                    href="<?= BASE_URL ?>/asistencias/listar.php">
                    <span class="app-menu-icon">
                        ✓
                    </span>

                    <div>
                        <strong>Asistencia</strong>
                        <span>Registrar llegadas</span>
                    </div>
                </a>

                <a
                    class="app-menu-card"
                    href="<?= BASE_URL ?>/pagos/listar.php">
                    <span class="app-menu-icon">
                        $
                    </span>

                    <div>
                        <strong>Pagos</strong>
                        <span>Cobros e historial</span>
                    </div>
                </a>

                <a
                    class="app-menu-card"
                    href="<?= BASE_URL ?>/planeaciones/listar.php">
                    <span class="app-menu-icon">
                        ≡
                    </span>

                    <div>
                        <strong>Planeaciones</strong>
                        <span>Rutinas personalizadas</span>
                    </div>
                </a>

                <a
                    class="app-menu-card"
                    href="<?= BASE_URL ?>/ejercicios/listar.php">
                    <span class="app-menu-icon">
                        +
                    </span>

                    <div>
                        <strong>Ejercicios</strong>
                        <span>Catálogo del gimnasio</span>
                    </div>
                </a>

            </section>


        </section>

    </main>
    <?php require __DIR__ . '/partials/mobile_nav.php'; ?>
    <script src="<?= BASE_URL ?>/js/pwa.js"></script>
</body>

</html>