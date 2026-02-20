<?php
require 'conexion.php';

ob_start();

// 1. All tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "=== ALL TABLES ===\n";
foreach ($tables as $t)
    echo "  $t\n";

// 2. usuarios columns
echo "\n=== USUARIOS COLUMNS ===\n";
$cols = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c)
    echo "  {$c['Field']} | {$c['Type']} | Key:{$c['Key']}\n";

// 3. sucursales
if (in_array('sucursales', $tables)) {
    echo "\n=== SUCURSALES COLUMNS ===\n";
    $cols = $pdo->query("DESCRIBE sucursales")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo "  {$c['Field']} | {$c['Type']}\n";
    echo "\n=== SUCURSALES ROWS ===\n";
    $rows = $pdo->query("SELECT * FROM sucursales LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r)
        echo "  " . json_encode($r) . "\n";
}

// 4. empresas
if (in_array('empresas', $tables)) {
    echo "\n=== EMPRESAS COLUMNS ===\n";
    $cols = $pdo->query("DESCRIBE empresas")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo "  {$c['Field']} | {$c['Type']}\n";
    echo "\n=== EMPRESAS ROWS ===\n";
    $rows = $pdo->query("SELECT * FROM empresas LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r)
        echo "  " . json_encode($r) . "\n";
}

// 5. tickets sucursal columns
if (in_array('tickets', $tables)) {
    echo "\n=== TICKETS COLUMNS ===\n";
    $cols = $pdo->query("DESCRIBE tickets")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo "  {$c['Field']} | {$c['Type']}\n";
}

// 6. Check for FK relationships
echo "\n=== FOREIGN KEYS INVOLVING usuarios/sucursales/empresas ===\n";
$fks = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_NAME IS NOT NULL
    AND TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $fk) {
    echo "  {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
}

$out = ob_get_clean();
file_put_contents('check_sucursales_out.txt', $out);
echo "Done. Output written to check_sucursales_out.txt\n";
?>