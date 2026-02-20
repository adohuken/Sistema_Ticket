<?php
require 'conexion.php';
ob_start();
// Check logo keys in configuracion_sistema
$rows = $pdo->query("SELECT clave, LEFT(valor,80) as val_preview FROM configuracion_sistema ORDER BY clave")->fetchAll(PDO::FETCH_ASSOC);
echo "=== CONFIGURACION_SISTEMA ===\n";
foreach ($rows as $r)
    echo "  {$r['clave']} => {$r['val_preview']}\n";

// Check empresas
echo "\n=== EMPRESAS ===\n";
$rows = $pdo->query("SELECT id, nombre, codigo FROM empresas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    echo "  [{$r['id']}] {$r['nombre']} (codigo: {$r['codigo']})\n";

// Check current empresa_asignada values in usuarios
echo "\n=== USUARIOS empresa_asignada ===\n";
$rows = $pdo->query("SELECT id, nombre_completo, empresa_asignada, empresa_id FROM usuarios ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    echo "  [{$r['id']}] {$r['nombre_completo']} | empresa_asignada='{$r['empresa_asignada']}' | empresa_id={$r['empresa_id']}\n";

file_put_contents('check_empresa_logos_out.txt', ob_get_clean());
echo "Done\n";
?>