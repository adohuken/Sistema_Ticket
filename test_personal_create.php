<?php
// test_personal_create.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
$_SESSION['csrf_token'] = 'VALID_TOKEN';
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'SuperAdmin';

require_once 'conexion.php';

// Override campo_csrf
if (!function_exists('campo_csrf')) {
    function campo_csrf()
    {
        return '';
    }
}

// SIMULATE ATTACK
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nombres'] = 'Hacker';
// NO CSRF ITEM

ob_start();
include 'seccion_personal_formulario.php';
$output = ob_get_clean();

if (strpos($output, 'Token inválido') !== false || strpos($output, 'Error de seguridad') !== false) {
    echo "✅ [PASS] Personal Create rejected invalid CSRF.\n";
} else {
    echo "❌ [FAIL] Personal Create accepted request or error not found.\n";
    // echo "Debug: " . substr(strip_tags($output), 0, 100) . "\n";
}
?>