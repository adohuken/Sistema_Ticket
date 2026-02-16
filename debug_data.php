<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';

echo "--- INVENTARIO COUNT ---\n";
try {
    $res = $pdo->query("SELECT count(*) as total, sum(case when estado='Activo' then 1 else 0 end) as activos FROM inventario")->fetch(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}

echo "\n--- INVENTARIO SAMPLE ---\n";
try {
    $res = $pdo->query("SELECT id, tipo, estado, asignado_a FROM inventario LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}

echo "\n--- EMPRESAS ---\n";
try {
    $res = $pdo->query("SELECT count(*) FROM empresas")->fetchColumn();
    echo "Total Empresas: $res\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>