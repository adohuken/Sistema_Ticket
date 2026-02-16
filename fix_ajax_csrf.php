<?php
// Patching index.php for AJAX CSRF - Attempt 2
$indexFile = 'index.php';
$content = file_get_contents($indexFile);

// Exact match based on view_file output (8 spaces indentation)
$search = "        if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
            if (\$action === 'asignar_equipo') {";

$replace = "        if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Check
            if (!isset(\$_POST['csrf_token']) || !validar_csrf_token(\$_POST['csrf_token'])) {
                 echo json_encode(['status' => 'error', 'msg' => 'Error de seguridad: Token CSRF inválido']);
                 exit;
            }

            if (\$action === 'asignar_equipo') {";

if (strpos($content, $search) !== false) {
    if (strpos($content, "Token CSRF inválido", strpos($content, "if (\$_SERVER['REQUEST_METHOD'] === 'POST')")) === false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($indexFile, $content);
        echo "Secured index.php AJAX.\n";
    } else {
        echo "index.php AJAX already secured.\n";
    }
} else {
    echo "Could not find index.php AJAX block. Dumping context for debug:\n";
    // Debug: find the line and show it
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (strpos($line, "REQUEST_METHOD'] === 'POST'") !== false && strpos($lines[$i + 1] ?? '', "asignar_equipo") !== false) {
            echo "Found at line " . ($i + 1) . ":\n" . $line . "\n" . ($lines[$i + 1] ?? '') . "\n";
            // Check hex dump of whitespace?
            echo "Line hex: " . bin2hex($line) . "\n";
        }
    }
}
?>