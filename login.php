<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

redirigirSiEstaAutenticado();

$mensajeError = $_SESSION['mensaje_error'] ?? null;

unset($_SESSION['mensaje_error']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar sesión | Gym Box</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/css/login.css"
    >

    <?php require __DIR__ . '/partials/pwa_head.php'; ?>
</head>

<body>

    <main class="login-container">

        <section class="login-card">

            <header class="login-header">
                <h1>Gym Box</h1>
                <p>Control administrativo y deportivo</p>
            </header>

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
                action="<?= BASE_URL ?>/auth/procesar_login.php"
                method="POST"
                autocomplete="off"
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

                <div class="form-group">
                    <label for="usuario">
                        Usuario
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        id="usuario"
                        name="usuario"
                        maxlength="60"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    class="btn-login"
                    type="submit"
                >
                    Iniciar sesión
                </button>

            </form>

        </section>

        <footer class="login-footer">
            Sistema de control para gimnasio de box
        </footer>

    </main>
<script src="<?= BASE_URL ?>/js/pwa.js"></script>
</body>
</html>