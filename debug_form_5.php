<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';
try {
    $stmt = $pdo->query('SELECT * FROM formularios_rrhh WHERE id = 5');
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($f) {
        print_r($f);
        echo "Tipo length: " . strlen($f['tipo']);
    } else {
        echo "Not found";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
