<?php
require_once 'conexion.php';

$stmt = $pdo->query("SELECT id, marca, modelo, estado FROM inventario");
echo "ID | Marca | Modelo | Estado (Raw Value)\n";
echo "---|---|---|---\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estado_val = $row['estado'];
    if (is_null($estado_val))
        $estado_val = 'NULL';
    if ($estado_val === '')
        $estado_val = "'' (Empty String)";

    echo "{$row['id']} | {$row['marca']} | {$row['modelo']} | $estado_val\n";
}
?>