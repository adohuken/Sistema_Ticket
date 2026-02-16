<?php
require_once 'conexion.php';

$bad_suffixes = [
    'De Master Honduras',
    'De Master Leon',
    'De Sebaco',
    'De Leon',
    'De Suministros Honduras',
    'De Republica Dominicana',
    'De El Salvador',
    'De Costa Rica',
    'De Guatemala'
];

$stmt = $pdo->query("SELECT id, nombres, apellidos FROM personal");
$count = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $apellidos = $row['apellidos'];
    $nombres = $row['nombres'];

    $new_apellidos = $apellidos;
    $new_nombres = $nombres;
    $changed = false;

    // Special fix for Glendon
    if (stripos($nombres, 'Glendon') !== false && stripos($apellidos, 'El Salvador') !== false) {
        $new_nombres = "Glendon";
        $new_apellidos = "Aleman";
        $changed = true;
    } else {
        // General cleanup
        foreach ($bad_suffixes as $suffix) {
            if (stripos($apellidos, $suffix) !== false) {
                $new_apellidos = str_ireplace($suffix, '', $apellidos);
                $new_apellidos = trim($new_apellidos);

                // If surname became empty, set a default depending on name
                if (empty($new_apellidos)) {
                    if (strpos($nombres, 'Test') !== false) {
                        $new_apellidos = 'Prueba';
                    } else {
                        $new_apellidos = 'Apellido';
                    }
                }
                $changed = true;
            }
        }
    }

    if ($changed) {
        $update = $pdo->prepare("UPDATE personal SET nombres = ?, apellidos = ? WHERE id = ?");
        $update->execute([$new_nombres, $new_apellidos, $id]);
        echo "Updated ID $id: '$nombres' '$apellidos' -> '$new_nombres' '$new_apellidos'\n";
        $count++;
    }
}

echo "Total updated: $count\n";
?>