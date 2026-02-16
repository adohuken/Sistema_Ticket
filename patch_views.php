<?php
$file = 'index.php';
$content = file_get_contents($file);

$search = 'default:
        echo "<div class=\'p-6\'>Vista no encontrada.</div>";
        break;';

// Normalize line endings
$content_norm = str_replace("\r\n", "\n", $content);
$search_norm = str_replace("\r\n", "\n", $search);

// Find insertion point (before default)
$pos = strpos($content_norm, $search_norm);

// If strict search fails, try searching just for "default:" 
if ($pos === false) {
    $search_relaxed = 'default:';
    $pos = strpos($content, $search_relaxed);
    if ($pos === false) {
        die("Could not find default switch case in index.php");
    }
} else {
    // If strict search worked, map back to original content pos
    // Since strpos on norm might mismatch if bytes differ, use relaxed search in original content to be safe.
    $search_relaxed = 'default:';
    $pos = strpos($content, $search_relaxed);
}

// Ensure we are inside the switch (last occurrence?)
// Actually, default is usually at the end.
$insert_pos = $pos;

$new_code = "
    case 'backup':
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {
            include __DIR__ . '/backup_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;

    case 'restore':
        if (\$rol_usuario === 'SuperAdmin' || in_array('restaurar_bd', \$permisos_usuario ?? [])) {
             echo \"<div class='p-6 text-slate-500'>Módulo de Restauración no disponible.</div>\";
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;

    case 'restart':
        if (\$rol_usuario === 'SuperAdmin' || in_array('reiniciar_bd', \$permisos_usuario ?? [])) {
            include __DIR__ . '/reiniciar_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;

";

$new_content = substr_replace($content, $new_code, $insert_pos, 0);
file_put_contents($file, $new_content);

echo "Successfully patched index.php with system views";
?>