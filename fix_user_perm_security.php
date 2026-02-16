<?php
$file = 'index.php';
$content = file_get_contents($file);

// Target the start of the User Permission block
$search = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'actualizar_permisos_usuario' && (\$rol_usuario === 'Admin' || \$rol_usuario === 'SuperAdmin')) {";

// Define replacement (Inject CSRF check)
$replace = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'actualizar_permisos_usuario' && (\$rol_usuario === 'Admin' || \$rol_usuario === 'SuperAdmin')) {
            // CSRF Check (Injected)
            if (!isset(\$_POST['csrf_token']) || !validar_csrf_token(\$_POST['csrf_token'])) {
                 throw new Exception('Error de seguridad: Token CSRF inválido');
            }";

// Only replace if we find the search string AND we don't see the CSRF check immediately after
// Simple check: does the block already contain "Token CSRF inválido"?
// We search for the *first* occurrence of the block (since duplicate is deleted).

if (strpos($content, $search) !== false) {
    // Check if likely already patched
    $pos = strpos($content, $search);
    $check_area = substr($content, $pos, 300); // Check next 300 chars
    if (strpos($check_area, "Token CSRF inválido") === false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($file, $content);
        echo "Secured User Permission Update.";
    } else {
        echo "User Permission Update already secured.";
    }
} else {
    echo "Could not find User Permission Update block.";
}
?>