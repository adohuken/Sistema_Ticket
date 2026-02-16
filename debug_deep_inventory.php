<?php
require_once 'conexion.php';

$id = 2;
echo "--- BEFORE UPDATE ---\n";
$stmt = $pdo->prepare("SELECT id, estado FROM inventario WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);

echo "--- EXECUTING UPDATE TO 'TEST' ---\n";
$update = $pdo->prepare("UPDATE inventario SET estado = 'TEST' WHERE id = ?");
$update->execute([$id]);
echo "Rows from UPDATE: " . $update->rowCount() . "\n";

echo "--- AFTER UPDATE ---\n";
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);

echo "--- RESETTING TO 'Bueno' ---\n";
$reset = $pdo->prepare("UPDATE inventario SET estado = 'Bueno' WHERE id = ?");
$reset->execute([$id]);
echo "Rows from RESET: " . $reset->rowCount() . "\n";
?>