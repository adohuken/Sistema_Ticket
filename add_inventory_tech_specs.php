<?php
require_once 'conexion.php';

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN procesador VARCHAR(100) NULL AFTER sku");
    echo "Columna 'procesador' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'procesador' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN ram VARCHAR(50) NULL AFTER procesador");
    echo "Columna 'ram' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'ram' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN disco_duro VARCHAR(100) NULL AFTER ram");
    echo "Columna 'disco_duro' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'disco_duro' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN sistema_operativo VARCHAR(100) NULL AFTER disco_duro");
    echo "Columna 'sistema_operativo' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'sistema_operativo' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN ip_address VARCHAR(45) NULL AFTER sistema_operativo");
    echo "Columna 'ip_address' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'ip_address' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN mac_address VARCHAR(45) NULL AFTER ip_address");
    echo "Columna 'mac_address' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'mac_address' ya existe o error: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE inventario ADD COLUMN anydesk_id VARCHAR(50) NULL AFTER mac_address");
    echo "Columna 'anydesk_id' agregada.\n";
} catch (PDOException $e) {
    echo "Columna 'anydesk_id' ya existe o error: " . $e->getMessage() . "\n";
}

echo "Migración completada.";
?>