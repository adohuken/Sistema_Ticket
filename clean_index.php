<?php
$file = 'index.php';
$content = file_get_contents($file);

// Replace Backup Case with clean version
$search_backup = "case 'backup':
        echo \"<div style='background:lime; padding:20px; font-size:16px;'>DEBUG TOP: VIEW=BACKUP DETECTED. User=\$rol_usuario</div>\";
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {
             echo \"<div style='background:cyan;'>DEBUG: Permission Granted. Including file...</div>\";
            include __DIR__ . '/backup_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado (TOP CHECK). Permisos: \" . implode(',', \$permisos_usuario ?? []) . \"</div>\";
        }
        break;";

$replace_backup = "case 'backup':
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {
            include __DIR__ . '/backup_bd.php';
        } else {
            echo \"<div class='p-6 text-red-500'>Acceso denegado.</div>\";
        }
        break;";

$content = str_replace($search_backup, $replace_backup, $content);

file_put_contents($file, $content);
echo "Cleaned index.php";
?>