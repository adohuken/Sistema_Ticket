<?php
require_once 'conexion.php';

try {
    $columns = [
        "ADD COLUMN procesador VARCHAR(100) NULL AFTER serial",
        "ADD COLUMN ram VARCHAR(50) NULL AFTER procesador",
        "ADD COLUMN disco_duro VARCHAR(100) NULL AFTER ram",
        "ADD COLUMN antivirus VARCHAR(50) NULL AFTER disco_duro",
        "ADD COLUMN onedrive VARCHAR(50) NULL AFTER antivirus",
        "ADD COLUMN backup_status VARCHAR(50) NULL AFTER onedrive",
        "ADD COLUMN screenconnect VARCHAR(50) NULL AFTER backup_status"
    ];

    foreach ($columns as $col) {
        try {
            $pdo->exec("ALTER TABLE inventario $col");
            echo "Columna agregada: $col\n";
        } catch (PDOException $e) {
            // Ignorar si ya existe (Código 42S21 usualmente, o mensaje genérico)
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "Columna ya existe: $col\n";
            } else {
                echo "Error agregando columna: $col - " . $e->getMessage() . "\n";
            }
        }
    }
    echo "Actualización de base de datos completada.\n";

} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>