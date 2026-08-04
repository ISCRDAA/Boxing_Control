<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

try {
    $consulta = $pdo->query('SELECT DATABASE() AS base_datos, NOW() AS fecha_hora');
    $resultado = $consulta->fetch();

    $baseDatos = htmlspecialchars(
        (string) $resultado['base_datos'],
        ENT_QUOTES,
        'UTF-8'
    );

    $fechaHora = htmlspecialchars(
        (string) $resultado['fecha_hora'],
        ENT_QUOTES,
        'UTF-8'
    );
} catch (PDOException $e) {
    exit('La conexión existe, pero ocurrió un error al consultar: ' . $e->getMessage());
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

    <title>Prueba de conexión</title>
</head>

<body>

    <h1>Conexión realizada correctamente</h1>

    <p>
        <strong>Base de datos:</strong>
        <?= $baseDatos ?>
    </p>

    <p>
        <strong>Fecha y hora de MySQL:</strong>
        <?= $fechaHora ?>
    </p>

</body>
</html>