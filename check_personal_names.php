<?php
require_once 'conexion.php';
$stmt = $pdo->query("SELECT id, nombres, apellidos FROM personal");
echo "ID | Nombres | Apellidos\n";
echo "---|---|---\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} | {$row['nombres']} | {$row['apellidos']}\n";
}
?>