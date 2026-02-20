<?php
require 'conexion.php';
$s = $pdo->query("SELECT m.nombre FROM permisos_roles pr JOIN modulos m ON pr.modulo_id=m.id WHERE pr.rol_id=3 ORDER BY m.nombre");
$perms = $s->fetchAll(PDO::FETCH_COLUMN);
echo "Tecnico role permissions:\n";
foreach ($perms as $p)
    echo "- $p\n";

$needed = ['mis_tareas', 'historial_tecnico', 'reportes'];
foreach ($needed as $n) {
    echo in_array($n, $perms) ? "OK: $n\n" : "MISSING: $n\n";
}
?>