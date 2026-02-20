<?php
require 'conexion.php';
$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "Roles:\n";
foreach ($roles as $r)
    echo "- [{$r['id']}] {$r['nombre']}\n";
?>