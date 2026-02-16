<?php
require_once __DIR__ . '/conexion.php';

echo "=== Buscando todas las columnas ENUM de estado ===\n\n";

try {
    // Obtener todas las tablas de la base de datos
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $tablas_con_estado = [];

    foreach ($tables as $table) {
        // Obtener columnas de cada tabla
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            if ($col['Field'] === 'estado' && strpos($col['Type'], 'enum') !== false) {
                $tablas_con_estado[] = [
                    'tabla' => $table,
                    'tipo' => $col['Type'],
                    'default' => $col['Default'],
                    'null' => $col['Null']
                ];
            }
        }
    }

    if (empty($tablas_con_estado)) {
        echo "No se encontraron otras tablas con columna 'estado' ENUM\n";
    } else {
        echo "Tablas encontradas con columna 'estado' ENUM:\n\n";
        foreach ($tablas_con_estado as $info) {
            echo "Tabla: {$info['tabla']}\n";
            echo "  Tipo: {$info['tipo']}\n";
            echo "  Default: {$info['default']}\n";
            echo "  Permite NULL: {$info['null']}\n";

            // Verificar si tiene valores antiguos
            if (strpos($info['tipo'], 'Abierto') !== false || strpos($info['tipo'], 'En Progreso') !== false) {
                echo "  ⚠️  NECESITA ACTUALIZACIÓN\n";
            } else {
                echo "  ✅ Ya actualizada\n";
            }
            echo "\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>