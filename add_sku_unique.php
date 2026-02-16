<?php
require_once 'conexion.php';

try {
    // Intentar agregar índice UNIQUE al campo SKU
    // Primero verificamos si ya existe para no causar error fatal
    $sql = "SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema=DATABASE() AND table_name='inventario' AND index_name='idx_sku_unique'";
    $stmt = $pdo->query($sql);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Asegurarse de que no haya duplicados antes de agregar UNIQUE
        // Esto es una medida de seguridad simple, borrar duplicados o los que sean nulos/vacios no es ideal automaticamente sin avisar, 
        // pero asumiremos que como es nueva la columna, esta mayormente vacía o con datos nuevos.
        // Si hay duplicados el ALTER fallará.

        $pdo->exec("ALTER TABLE inventario ADD CONSTRAINT idx_sku_unique UNIQUE (sku)");
        echo "✅ Restricción UNIQUE agregada correctamente al campo 'sku'.";
    } else {
        echo "ℹ️ El campo 'sku' ya tiene una restricción UNIQUE.";
    }

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "❌ Error: No se puede hacer UNIQUE el campo 'sku' porque ya existen valores duplicados en la base de datos. Corrígelos manualmente primero.";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
?>