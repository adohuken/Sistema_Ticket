<?php
require 'conexion.php';

// 1. Add logo_key column to empresas if not exists
try {
    $pdo->exec("ALTER TABLE empresas ADD COLUMN logo_key VARCHAR(50) NULL DEFAULT NULL AFTER codigo");
    echo "Added logo_key column to empresas\n";
} catch (PDOException $e) {
    echo "logo_key column already exists or error: " . $e->getMessage() . "\n";
}

// 2. Set logo_key values based on existing mapping
$pdo->exec("UPDATE empresas SET logo_key = 'logo_mastertec' WHERE id = 1");
$pdo->exec("UPDATE empresas SET logo_key = 'logo_master_suministros' WHERE id = 3");
$pdo->exec("UPDATE empresas SET logo_key = 'logo_centro' WHERE id = 4");
echo "Updated logo_key values\n";

// 3. Migrate empresa_asignada (string) to empresa_id (int) for existing users
$enum_to_id = ['mastertec' => 1, 'suministros' => 3, 'centro' => 4];
$users = $pdo->query("SELECT id, empresa_asignada FROM usuarios WHERE empresa_asignada IS NOT NULL AND empresa_asignada != ''")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    $empresa_id = $enum_to_id[$u['empresa_asignada']] ?? null;
    if ($empresa_id) {
        $pdo->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ? AND (empresa_id IS NULL OR empresa_id = 0)")->execute([$empresa_id, $u['id']]);
        echo "Migrated user {$u['id']}: empresa_asignada='{$u['empresa_asignada']}' -> empresa_id=$empresa_id\n";
    }
}

// 4. Verify
echo "\n=== EMPRESAS with logo_key ===\n";
$rows = $pdo->query("SELECT id, nombre, codigo, logo_key FROM empresas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    echo "  [{$r['id']}] {$r['nombre']} | logo_key={$r['logo_key']}\n";

echo "\n=== USUARIOS empresa_id ===\n";
$rows = $pdo->query("SELECT u.id, u.nombre_completo, u.empresa_asignada, u.empresa_id, e.nombre as empresa_nombre FROM usuarios u LEFT JOIN empresas e ON u.empresa_id = e.id ORDER BY u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r)
    echo "  [{$r['id']}] {$r['nombre_completo']} | empresa_asignada='{$r['empresa_asignada']}' | empresa_id={$r['empresa_id']} ({$r['empresa_nombre']})\n";
?>