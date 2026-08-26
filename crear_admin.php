<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

/*
|--------------------------------------------------------------------------
| Datos temporales del administrador
|--------------------------------------------------------------------------
|
| Después podremos crear usuarios desde el propio sistema.
|
*/

$nombre = 'COACH DIEGO SG';
$usuario = 'Diego';
$passwordPlano = 'Culebras1';
$rol = 'Coach';

/*
$nombre = 'Administrador General';
$usuario = 'admin';
$passwordPlano = 'BoxGym2026*';
$rol = 'administrador';

*/
try {
    $consulta = $pdo->prepare(
        'SELECT id
         FROM usuarios
         WHERE usuario = :usuario
         LIMIT 1'
    );

    $consulta->execute([
        'usuario' => $usuario,
    ]);

    $usuarioExistente = $consulta->fetch();

    if ($usuarioExistente) {
        $mensaje = 'El usuario administrador ya existe.';
        $creado = false;
    } else {
        $passwordHash = password_hash(
            $passwordPlano,
            PASSWORD_DEFAULT
        );

        $insertar = $pdo->prepare(
            'INSERT INTO usuarios (
                nombre,
                usuario,
                password_hash,
                rol,
                activo
            ) VALUES (
                :nombre,
                :usuario,
                :password_hash,
                :rol,
                1
            )'
        );

        $insertar->execute([
            'nombre' => $nombre,
            'usuario' => $usuario,
            'password_hash' => $passwordHash,
            'rol' => $rol,
        ]);

        $mensaje = 'Administrador creado correctamente.';
        $creado = true;
    }
} catch (PDOException $e) {
    exit(
        'Ocurrió un error al crear el administrador: ' .
        $e->getMessage()
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

    <title>Crear administrador</title>
</head>

<body>

    <h1><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if ($creado): ?>

        <p>
            <strong>Usuario:</strong>
            <?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <p>
            <strong>Contraseña temporal:</strong>
            <?= htmlspecialchars($passwordPlano, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php endif; ?>

    <p>
        Después de comprobar que el administrador existe,
        elimina el archivo <strong>crear_admin.php</strong>.
    </p>

    <a href="/Boxing_Control/login.php">
        Ir al inicio de sesión
    </a>

</body>
</html>