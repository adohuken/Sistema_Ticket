<?php
require __DIR__ . '/conexion.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$out = "Tables:\n" . print_r($tables, true) . "\n";

foreach ($tables as $table) {
    if (strpos($table, 'inventario') !== false || strpos($table, 'historial') !== false || strpos($table, 'asignacion') !== false || strpos($table, 'personal') !== false) {
        $stmt2 = $pdo->query("SHOW COLUMNS FROM `$table`");
        $out .= "\nColumns in $table:\n" . print_r($stmt2->fetchAll(PDO::FETCH_COLUMN), true);
    }
}
file_put_contents('db_schema.txt', $out);
