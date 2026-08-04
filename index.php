<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';

if (usuarioAutenticado()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;