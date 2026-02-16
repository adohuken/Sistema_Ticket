<?php
// Patching index.php for AJAX CSRF - Attempt 3 (Precise)
$indexFile = 'index.php';
$content = file_get_contents($indexFile);

// Target:
//         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//             if ($action === 'asignar_equipo') {

$search = "        if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
            if (\$action === 'asignar_equipo') {";

$replace = "        if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Check
            if (!isset(\$_POST['csrf_token']) || !validar_csrf_token(\$_POST['csrf_token'])) {
                 echo json_encode(['status' => 'error', 'msg' => 'Error de seguridad: Token CSRF inválido']);
                 exit;
            }

            if (\$action === 'asignar_equipo') {";

$pos = strpos($content, $search);
if ($pos !== false) {
    // Check if check already exists in next 200 chars
    $snippet = substr($content, $pos, 200);
    if (strpos($snippet, "Token CSRF") === false) {
        $content = str_replace($search, $replace, $content);
        file_put_contents($indexFile, $content);
        echo "Secured index.php AJAX (Likely Success).\n";
    } else {
        echo "index.php AJAX already secured (Verified Local Context).\n";
    }
} else {
    echo "Could not find index.php AJAX block.\n";
}
?>