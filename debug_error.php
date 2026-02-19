<?php
// debug_error.php - Script para diagnosticar Errores 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<div style='font-family:monospace; background:#fff; color:#000; padding:20px;'>";
echo "<h2>🛠️ Diagnóstico de Sistema</h2>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<hr><h3>1. Probando conexión a Base de Datos ('conexion.php')...</h3>";
try {
    if (file_exists('conexion.php')) {
        require_once 'conexion.php';
        if (isset($pdo)) {
            echo "<span style='color:green'>✓ Conexión exitosa a BD.</span><br>";
        } else {
            echo "<span style='color:red'>✗ Archivo incluido pero $pdo no existe.</span><br>";
        }
    } else {
        echo "<span style='color:red'>✗ No se encuentra conexion.php</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>✗ Error Crítico DB: " . $e->getMessage() . "</span><br>";
}

echo "<hr><h3>2. Intentando cargar 'index.php'...</h3>";
echo "<em>Si hay un error de sintaxis, se mostrará abajo:</em><br><br>";

if (file_exists('index.php')) {
    // Intentar incluir index.php. Si falla, el error fatal se mostrará gracias a display_errors
    include 'index.php';
    echo "<br><br><span style='color:green'>✓ index.php cargó completamente (el script llegó al final).</span>";
} else {
    echo "<span style='color:red'>✗ CRÍTICO: No se encuentra index.php en el directorio.</span>";
}

echo "</div>";
?>