<?php
require_once __DIR__ . '/conexion.php';

echo "=== Arreglando Estados Vacíos - Método Directo ===\n\n";

try {
    $ids_vacios = [5, 6, 8, 9, 10, 11, 13, 14];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
    $actualizados = 0;

    foreach ($ids_vacios as $id) {
        $stmt->execute(['Pendiente', $id]);
        if ($stmt->rowCount() > 0) {
            $actualizados++;
            echo "✅ Ticket #$id actualizado a 'Pendiente'\n";
        }
    }

    $pdo->commit();

    echo "\n✅ Total actualizados: $actualizados tickets\n\n";

    // Verificar resultado final
    echo "=== Resumen Final de Estados ===\n";
    $stmt = $pdo->query("SELECT estado, COUNT(*) as cantidad FROM tickets GROUP BY estado ORDER BY estado");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $estado_display = $row['estado'] ?: '[VACÍO]';
        echo sprintf("%-20s: %d tickets\n", $estado_display, $row['cantidad']);
    }

    echo "\n✅ Proceso completado!\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>