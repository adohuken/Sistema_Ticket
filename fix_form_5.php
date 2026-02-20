<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';
try {
    $stmt = $pdo->prepare("UPDATE formularios_rrhh SET tipo = 'Licencia' WHERE id = 5");
    $stmt->execute();
    echo "Updated rows: " . $stmt->rowCount();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
