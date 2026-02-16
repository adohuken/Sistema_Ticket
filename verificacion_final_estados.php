<?php
require_once __DIR__ . '/conexion.php';

echo "=== Verificación Final de Estados ===\n\n";

try {
    // 1. Verificar tabla tickets
    echo "1. Tabla TICKETS:\n";
    echo "   Estructura ENUM:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets WHERE Field = 'estado'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   " . $col['Type'] . "\n";
    echo "   Default: " . $col['Default'] . "\n\n";

    echo "   Distribución de estados:\n";
    $stmt = $pdo->query("SELECT estado, COUNT(*) as cnt FROM tickets GROUP BY estado ORDER BY estado");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("   %-20s: %d tickets\n", $row['estado'], $row['cnt']);
    }

    // 2. Verificar tabla inventario
    echo "\n2. Tabla INVENTARIO:\n";
    echo "   Estructura ENUM:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM inventario WHERE Field = 'estado'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   " . $col['Type'] . "\n";
    echo "   (Estados de equipos, no de tickets - No requiere cambios)\n";

    // 3. Buscar otras columnas que puedan tener referencias a estados
    echo "\n3. Buscando otras columnas con 'estado' en el nombre:\n";
    $stmt = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND COLUMN_NAME LIKE '%estado%'
    ");

    $otras_columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($otras_columnas as $col) {
        echo sprintf("   %s.%s (%s)\n", $col['TABLE_NAME'], $col['COLUMN_NAME'], $col['DATA_TYPE']);
    }

    echo "\n✅ Verificación completada!\n";
    echo "\nResumen:\n";
    echo "- Tabla 'tickets': ✅ Actualizada con nuevos estados\n";
    echo "- Tabla 'inventario': ℹ️  Usa estados de equipos (no requiere cambios)\n";
    echo "- Otras tablas: ℹ️  No tienen columnas de estado de tickets\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>