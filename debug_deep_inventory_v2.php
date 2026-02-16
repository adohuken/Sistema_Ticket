<?php
require_once 'conexion.php';
$id = 2;

echo "--- CONNECTION INFO ---\n";
// Check attributes
echo "AutoCommit: " . $pdo->getAttribute(PDO::ATTR_AUTOCOMMIT) . "\n";
echo "ErrMode: " . $pdo->getAttribute(PDO::ATTR_ERRMODE) . "\n";

echo "--- ATTEMPT UPDATE ---\n";
$sql = "UPDATE inventario SET estado = 'TEST' WHERE id = 2";
$count = $pdo->exec($sql);

if ($count === false) {
    echo "Exec failed.\n";
    print_r($pdo->errorInfo());
} else {
    echo "Exec success. Rows: $count\n";
}

$stmt = $pdo->query("SELECT id, estado FROM inventario WHERE id=2");
var_dump($stmt->fetch(PDO::FETCH_ASSOC));
?>