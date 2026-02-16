<?php
$file = 'index.php';
$content = file_get_contents($file);

// 1. Secure registrar_ingreso
$search_ingreso = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'registrar_ingreso' && (\$rol_usuario === 'RRHH' || \$rol_usuario === 'SuperAdmin')) {";
$replace_ingreso = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'registrar_ingreso' && (\$rol_usuario === 'RRHH' || \$rol_usuario === 'SuperAdmin')) {
            // CSRF Check
            if (!isset(\$_POST['csrf_token']) || !validar_csrf_token(\$_POST['csrf_token'])) {
                \$mensaje_accion = \"<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error de seguridad: Token CSRF inválido</div>\";
            } else {";

// Note: We need to close the ELSE block later, or structured differently.
// Better approach: Throw exception or set error and skip logic.
// The original code does: try { ... } catch
// So injecting a check that throws exception is cleanest.

$search_ingreso_try = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'registrar_ingreso' && (\$rol_usuario === 'RRHH' || \$rol_usuario === 'SuperAdmin')) {
            try {";

$replace_ingreso_try = "if (isset(\$_POST['accion']) && \$_POST['accion'] === 'registrar_ingreso' && (\$rol_usuario === 'RRHH' || \$rol_usuario === 'SuperAdmin')) {
            try {
                // CSRF Check (Injected)
                if (!isset(\$_POST['csrf_token']) || !validar_csrf_token(\$_POST['csrf_token'])) {
                    throw new Exception('Error de seguridad: Token CSRF inválido');
                }";

if (strpos($content, $search_ingreso_try) !== false) {
    if (strpos($content, "Token CSRF inválido", strpos($content, $search_ingreso_try)) === false) {
        $content = str_replace($search_ingreso_try, $replace_ingreso_try, $content);
        echo "Secured registrar_ingreso.\n";
    } else {
        echo "registrar_ingreso already secured.\n";
    }
} else {
    echo "Could not find registrar_ingreso block via precise matching.\n";
}

// 2. Secure Ticket Creation (around line 1082/1083 in previous view, or generally POST create_ticket)
// Actually, ticket creation is usually `accion` = `crear_ticket`.
// Let's find: `if (isset($_POST['accion']) && $_POST['accion'] === 'crear_ticket'` or similar.
// I haven't seen the exact string in recent output, so I will search for it first or stick to known targets.
// I'll skip ticket creation patch in this script until I verify the code string.

file_put_contents($file, $content);
?>