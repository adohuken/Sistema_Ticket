<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';
$stmt = $pdo->query('DESCRIBE inventario');
foreach ($stmt->fetchAll() as $col) {
    echo $col['Field'] . "\n";
}
?>