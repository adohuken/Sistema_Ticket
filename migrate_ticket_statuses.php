<?php
require 'conexion.php';

try {
    echo "Iniciando migración de estados de tickets...\n";

    // Paso 1: Expandir ENUM para incluir nuevos estados
    echo "1. Expandiendo ENUM...\n";
    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN estado ENUM('Abierto','Pendiente','En Progreso','En Atención','Resuelto','Cerrado','Asignado','Completo') DEFAULT 'Pendiente'");

    // Paso 2: Migrar datos
    echo "2. Migrando datos...\n";

    // Pendiente / Abierto -> Pendiente
    $count = $pdo->exec("UPDATE tickets SET estado = 'Pendiente' WHERE estado = 'Abierto'");
    echo " - 'Abierto' -> 'Pendiente': $count registros actualizados.\n";

    // En Progreso / En Atención -> Asignado
    $count = $pdo->exec("UPDATE tickets SET estado = 'Asignado' WHERE estado IN ('En Progreso', 'En Atención')");
    echo " - 'En Progreso/En Atención' -> 'Asignado': $count registros actualizados.\n";

    // Resuelto / Cerrado -> Completo
    $count = $pdo->exec("UPDATE tickets SET estado = 'Completo' WHERE estado IN ('Resuelto', 'Cerrado')");
    echo " - 'Resuelto/Cerrado' -> 'Completo': $count registros actualizados.\n";

    // Paso 3: Restringir ENUM
    echo "3. Optimizando estructura de tabla (Restringiendo ENUM)...\n";
    $pdo->exec("ALTER TABLE tickets MODIFY COLUMN estado ENUM('Pendiente','Asignado','Completo') DEFAULT 'Pendiente'");

    echo "✅ Migración completada con éxito.\n";

} catch (PDOException $e) {
    echo "❌ Error durante la migración: " . $e->getMessage() . "\n";
}
?>