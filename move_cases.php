<?php
$file = 'index.php';
$content = file_get_contents($file);

// 1. Remove the old backup (and restore/restart) cases I added near default
// I'll search for the distinctive comment or code block
$search_old = "case 'backup':
        echo \"<div style='background:yellow; padding:10px; border:2px solid red; color:black;'>DEBUG: Backup Case Reached. Role: \$rol_usuario. Perms: \" . (is_array(\$permisos_usuario) ? implode(',', \$permisos_usuario) : 'NULL') . \"</div>\";
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {
            include __DIR__ . '/backup_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;";

// Need to match exactly what I wrote in add_debug.php
// It was:
/*
case 'backup':
        echo "<div style='background:yellow; padding:10px; border:2px solid red; color:black;'>DEBUG: Backup Case Reached. Role: $rol_usuario. Perms: " . (is_array($permisos_usuario) ? implode(',', $permisos_usuario) : 'NULL') . "</div>";
        if ($rol_usuario === 'SuperAdmin' || in_array('backup_bd', $permisos_usuario ?? [])) {
            include __DIR__ . '/backup_bd.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;
*/

// I'll use a regex or loose match to remove the block if possible, or just ignore it (duplicate cases might be an issue but switch usually takes first match - wait, PHP executes first match. If I put new one at top, it will run. Duplicate case might be error?)
// PHP Fatal error: Duplicate switching case? Likely.
// So I MUST remove the old one.

// Let's try to locate the start of it.
$start_pattern = "/case 'backup':.*break;/s";
// Use strpos to be safer.
$pos_old = strpos($content, "case 'backup':");
if ($pos_old !== false) {
    // Find the end of this case block (break;)
    $pos_break = strpos($content, "break;", $pos_old);
    if ($pos_break !== false) {
        $len = ($pos_break + 6) - $pos_old;
        $content = substr_replace($content, "", $pos_old, $len);
    }
}

// Remove restore/restart as well to be clean
$pos_restore = strpos($content, "case 'restore':");
if ($pos_restore !== false) {
    $pos_break = strpos($content, "break;", $pos_restore);
    if ($pos_break !== false)
        $content = substr_replace($content, "", $pos_restore, ($pos_break + 6) - $pos_restore);
}
$pos_restart = strpos($content, "case 'restart':");
if ($pos_restart !== false) {
    $pos_break = strpos($content, "break;", $pos_restart);
    if ($pos_break !== false)
        $content = substr_replace($content, "", $pos_restart, ($pos_break + 6) - $pos_restart);
}


// 2. Insert at the TOP of the switch
$search_switch = "switch (\$view) {";
$insert_pos = strpos($content, $search_switch);
if ($insert_pos === false)
    die("Switch not found");

$insert_pos += strlen($search_switch); // After the brace

$new_code = "
    case 'backup':
        echo \"<div style='background:lime; padding:20px; font-size:16px;'>DEBUG TOP: VIEW=BACKUP DETECTED. User=\$rol_usuario</div>\";
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {
             echo \"<div style='background:cyan;'>DEBUG: Permission Granted. Including file...</div>\";
            include __DIR__ . '/backup_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado (TOP CHECK). Permisos: \" . implode(',', \$permisos_usuario ?? []) . \"</div>\";
        }
        break;

    case 'restore':
         echo \"<div class='p-6 text-slate-500'>Restaurar no disponible</div>\";
         break;

    case 'restart':
        if (\$rol_usuario === 'SuperAdmin' || in_array('reiniciar_bd', \$permisos_usuario ?? [])) {
            include __DIR__ . '/reiniciar_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;
";

$content = substr_replace($content, $new_code, $insert_pos, 0);

file_put_contents($file, $content);
echo "Moved cases to top of switch";
?>