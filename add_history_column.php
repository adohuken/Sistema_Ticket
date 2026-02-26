<?php
require __DIR__ . '/conexion.php';
try {
    $pdo->exec('ALTER TABLE personal ADD COLUMN ultimos_activos_devueltos VARCHAR(255) NULL;');
    echo "Columna agregada exitosamente.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "La columna ya existe.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
