<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';
try {
    $pdo->exec("ALTER TABLE mantenimiento_equipos ADD COLUMN checklist TEXT NULL");
    echo "Column 'checklist' added successfully.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>