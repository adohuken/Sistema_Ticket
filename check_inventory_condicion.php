<?php
require_once 'conexion.php';

$stmt = $pdo->query("SELECT id, marca, modelo, condicion FROM inventario");
echo "ID | Marca | Modelo | Condicion (Raw Value)\n";
echo "---|---|---|---\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cond = $row['condicion'];
    if (is_null($cond))
        $cond = 'NULL';
    if ($cond === '')
        $cond = "'' (Empty String)";

    echo "{$row['id']} | {$row['marca']} | {$row['modelo']} | $cond\n";
}
?>