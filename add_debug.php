<?php
$file = 'index.php';
$content = file_get_contents($file);

$search = "case 'backup':
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {";

$replace = "case 'backup':
        echo \"<div style='background:yellow; padding:10px; border:2px solid red; color:black;'>DEBUG: Backup Case Reached. Role: \$rol_usuario. Perms: \" . (is_array(\$permisos_usuario) ? implode(',', \$permisos_usuario) : 'NULL') . \"</div>\";
        if (\$rol_usuario === 'SuperAdmin' || in_array('backup_bd', \$permisos_usuario ?? [])) {";

// Loose search because of whitespace
if (strpos($content, $search) === false) {
    // Try to find just case 'backup': and insert after it
    $search = "case 'backup':";
    $replace = "case 'backup':
        echo \"<div style='background:yellow; padding:10px; border:2px solid red; color:black;'>DEBUG: Backup Case Reached. Role: \$rol_usuario. Perms: \" . (is_array(\$permisos_usuario) ? implode(',', \$permisos_usuario) : 'NULL') . \"</div>\";";

    $content = str_replace($search, $replace, $content);
} else {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);
echo "Added debug to index.php";
?>