<?php
// test_personal_edit.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
$_SESSION['csrf_token'] = 'VALID_TOKEN';
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 'SuperAdmin'; // Permitir acceso

// Mock Database
require_once 'conexion.php';

// Override campo_csrf to avoid errors
if (!function_exists('campo_csrf')) {
    function campo_csrf()
    {
        return '';
    }
}

// SIMULATE ATTACK
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['id'] = 999; // ID arbitrario
$_POST['nombres'] = 'Hacker';
// NO CSRF ITEM

ob_start();
try {
    include 'seccion_personal_editar.php';
} catch (Exception $e) {
    // Should not catch here if the script handles exceptions internally, 
    // but seccion_personal_editar.php has a try/catch block that sets $mensaje_error.
}
$output = ob_get_clean();

// Check if $mensaje_error was populated or output contains the error
// Since the file is included, local variables like $mensaje_error might not leak out trivially 
// UNLESS we use global keywords or look at the echoed HTML.
// The script echoes $mensaje_error in the HTML.

if (strpos($output, 'Token inválido') !== false || strpos($output, 'Error de seguridad') !== false) {
    echo "✅ [PASS] Personal Edit rejected invalid CSRF.\n";
} else {
    echo "❌ [FAIL] Personal Edit accepted request or error not found.\n";
    // echo "Output snippet: " . substr(strip_tags($output), 0, 200) . "\n";
}

?>