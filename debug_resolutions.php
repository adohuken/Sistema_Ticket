<?php
require 'conexion.php';

$stmt = $pdo->query("SELECT id, estado, resolucion FROM tickets WHERE estado = 'Completo'");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "ID | Estado | Resolucion (Raw)\n";
echo "---|---|---\n";
foreach ($tickets as $t) {
    $res = $t['resolucion'];
    // Show [EMPTY] acts explicit vs just whitespace
    if (empty($res))
        $res = "[EMPTY]";
    echo "{$t['id']} | {$t['estado']} | {$res}\n";
}
