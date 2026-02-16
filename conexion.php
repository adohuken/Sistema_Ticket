<?php
/**
 * conexion.php - Archivo de conexión a la base de datos
 */

// Configurar zona horaria por defecto
date_default_timezone_set('America/Managua');

$host = 'localhost';
$db = 'sistema_ticket';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Detectar si estamos en el servidor de producción (InfinityFree)
// Puedes ajustar esta lógica si tu dominio cambia.
// Generalmente, en local $_SERVER['SERVER_NAME'] es 'localhost' o '127.0.0.1'
$is_production = false;

if (isset($_SERVER['SERVER_NAME'])) {
    if ($_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != '127.0.0.1') {
        $is_production = true;
    }
}

// Credenciales para InfinityFree
if ($is_production) {
    $host = 'sql302.infinityfree.com';
    $db = 'if0_41173914_sistema_ticket';
    $user = 'if0_41173914';
    $pass = 'Bv4SIkrcHkvgrJJ';
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En producción, es mejor no mostrar detalles técnicos del error al usuario final
    if ($is_production) {
        die("Error de conexión a la base de datos. Por favor intente más tarde.");
    } else {
        die("Error de conexión a la base de datos: " . $e->getMessage());
    }
}
?>