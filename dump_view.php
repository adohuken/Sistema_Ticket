<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración de base de datos
require_once 'conexion.php';

try {
    $stmt = $pdo->query("SHOW CREATE VIEW vista_personal_completo");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h1>Definición de vista_personal_completo</h1>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    if (isset($result['Create View'])) {
        echo "<h2>SQL para Producción:</h2>";
        echo "<textarea rows='20' cols='100'>" . htmlspecialchars($result['Create View']) . "</textarea>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>