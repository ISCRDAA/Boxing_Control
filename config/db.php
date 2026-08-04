<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Zona horaria
|--------------------------------------------------------------------------
|
| Evita que PHP registre una hora distinta a la de México.
|
*/

date_default_timezone_set('America/Mexico_City');

/*
|--------------------------------------------------------------------------
| Configuración de la base de datos
|--------------------------------------------------------------------------
*/

$dbHost = 'localhost';
$dbName = 'gym_box';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

/*
|--------------------------------------------------------------------------
| DSN de conexión
|--------------------------------------------------------------------------
|
| El DSN le indica a PDO qué servidor, base de datos y codificación utilizar.
|
*/

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

/*
|--------------------------------------------------------------------------
| Opciones de PDO
|--------------------------------------------------------------------------
*/

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        $dsn,
        $dbUser,
        $dbPass,
        $options
    );
} catch (PDOException $e) {
    http_response_code(500);

    exit(
        'No fue posible conectar con la base de datos. ' .
        'Detalle técnico: ' . $e->getMessage()
    );
}