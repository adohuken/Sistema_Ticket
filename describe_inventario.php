<?php
require_once 'conexion.php';
$stmt = $pdo->query("DESCRIBE inventario");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if ($r['Field'] === 'estado') {
        print_r($r);
    }
}
?>