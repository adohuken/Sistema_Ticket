<?php
$file = 'index.php';
$content = file_get_contents($file);

$search = '$mensaje_accion = "<div class=\'bg-red-100 text-red-800 p-4 rounded mb-4\'>Error al actualizar permisos: " . $e->getMessage() . "</div>";
            }
        }';

// Normalize line endings for search
$content_norm = str_replace("\r\n", "\n", $content);
$search_norm = str_replace("\r\n", "\n", $search);

// Check if search exists
if (strpos($content_norm, $search_norm) === false) {
    // Try relaxed search (just the message line and closing brace)
    $search_relaxed = '$mensaje_accion = "<div class=\'bg-red-100 text-red-800 p-4 rounded mb-4\'>Error al actualizar permisos: " . $e->getMessage() . "</div>";';
    $pos = strpos($content, $search_relaxed);
    if ($pos === false) {
        die("Could not find anchor point in index.php");
    }
    // Find the next two closing braces
    $pos = strpos($content, "}", $pos); // close catch
    $pos = strpos($content, "}", $pos + 1); // close if
    $insert_pos = $pos + 1; // After the closing brace
} else {
    $insert_pos = strpos($content_norm, $search_norm) + strlen($search_norm);
    // Adjust logic if needed for CR/LF original file
    // But since we found it in norm, we need position in original.
    // Let's stick to relaxed search logic which is safer on original content.
    $search_relaxed = '$mensaje_accion = "<div class=\'bg-red-100 text-red-800 p-4 rounded mb-4\'>Error al actualizar permisos: " . $e->getMessage() . "</div>";';
    $pos = strpos($content, $search_relaxed);
    $pos = strpos($content, "}", $pos);
    $pos = strpos($content, "}", $pos + 1);
    $insert_pos = $pos + 1;
}

$new_code = "\n\n        // 10b. Actualizar Permisos de Usuario (Específicos)
        if (isset(\$_POST['accion']) && \$_POST['accion'] === 'actualizar_permisos_usuario' && (\$rol_usuario === 'Admin' || \$rol_usuario === 'SuperAdmin')) {
            try {
                \$usuario_id = \$_POST['usuario_id'];
                \$modulos_seleccionados = \$_POST['modulos_extra'] ?? [];

                // Eliminar permisos anteriores
                \$stmt = \$pdo->prepare(\"DELETE FROM permisos_usuarios WHERE usuario_id = ?\");
                \$stmt->execute([\$_POST['usuario_id']]);

                // Insertar nuevos
                if (!empty(\$modulos_seleccionados)) {
                    \$stmt = \$pdo->prepare(\"INSERT INTO permisos_usuarios (usuario_id, modulo_id) VALUES (?, ?)\");
                    foreach (\$modulos_seleccionados as \$mod_id) {
                        \$stmt->execute([\$_POST['usuario_id'], \$mod_id]);
                    }
                }

                registrar_actividad(\"Actualizar Permisos Usuario\", \"Permisos extra actualizados para Usuario ID: \" . \$usuario_id, \$pdo);
                \$_SESSION['mensaje_exito'] = \"✅ Permisos de usuario actualizados correctamente.\";
                header(\"Location: index.php?view=permisos\");
                exit;

            } catch (Exception \$e) {
                 \$mensaje_accion = \"<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar permisos de usuario: \" . \$e->getMessage() . \"</div>\";
            }
        }";

$new_content = substr_replace($content, $new_code, $insert_pos, 0);
file_put_contents($file, $new_content);

echo "Successfully patched index.php";
?>