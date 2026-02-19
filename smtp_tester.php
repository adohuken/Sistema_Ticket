<?php
/**
 * smtp_tester.php
 * Script ligero para probar conexión SMTP sin PHPMailer
 */

header('Content-Type: application/json');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => 'Error fatal PHP: ' . $error['message']]);
    }
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $host = trim($_POST['host'] ?? '');
    $port = intval($_POST['port'] ?? 587);
    $user = trim($_POST['username'] ?? '');
    // Si la contraseña viene vacía, intentamos leer la guardada
    $pass = $_POST['password'] ?? '';
    if (empty($pass)) {
        $config_path = __DIR__ . '/config_mail.php';
        if (file_exists($config_path)) {
            $current = require $config_path;
            $saved_user = trim($current['username'] ?? '');

            // Comparación robusta: ignorar mayúsculas y espacios
            if (strtolower($saved_user) === strtolower($user)) {
                $pass = $current['password'] ?? '';
            } else {
                error_log("SMTP Tester: No coincide usuario. POST: '$user' vs Saved: '$saved_user'");
            }
        }
    }

    $enc = $_POST['encryption'] ?? 'tls';

    if (empty($host) || empty($user) || empty($pass)) {
        $missing = [];
        if (empty($host))
            $missing[] = 'Host';
        if (empty($user))
            $missing[] = 'Usuario';
        if (empty($pass))
            $missing[] = 'Contraseña';

        throw new Exception('Faltan datos de configuración: ' . implode(', ', $missing) . '. Si dejó la contraseña vacía, verifique que el usuario sea idéntico al guardado.');
    }

    $protocol = '';
    if ($enc === 'ssl')
        $protocol = 'ssl://';

    // 1. Conexión Socket
    $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, 10);
    if (!$socket) {
        throw new Exception("No se pudo conectar al host ($host:$port): $errstr ($errno)");
    }

    // Helper para leer/escribir
    if (!function_exists('smtp_cmd')) {
        function smtp_cmd($socket, $cmd = null)
        {
            if ($cmd)
                fwrite($socket, $cmd . "\r\n");
            $response = '';
            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (substr($line, 3, 1) == ' ')
                    break;
            }
            return $response;
        }
    }

    $resp = smtp_cmd($socket); // Banner inicial
    if (intval(substr($resp, 0, 3)) !== 220)
        throw new Exception("Respuesta inválida servidor: $resp");

    // HELO/EHLO
    $helo = smtp_cmd($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

    // STARTTLS
    if ($enc === 'tls') {
        $tls = smtp_cmd($socket, 'STARTTLS');
        if (intval(substr($tls, 0, 3)) !== 220)
            throw new Exception("Error STARTTLS: $tls");

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("Fallo al negociar encriptación TLS");
        }
        // Re-enviar EHLO tras TLS
        smtp_cmd($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    }

    // AUTH LOGIN
    $auth = smtp_cmd($socket, 'AUTH LOGIN');
    if (intval(substr($auth, 0, 3)) !== 334)
        throw new Exception("Servidor no soporta AUTH LOGIN o error: $auth");

    // User
    $user_resp = smtp_cmd($socket, base64_encode($user));
    if (intval(substr($user_resp, 0, 3)) !== 334)
        throw new Exception("Usuario rechazado: $user_resp");

    // Pass
    $pass_resp = smtp_cmd($socket, base64_encode($pass));
    if (intval(substr($pass_resp, 0, 3)) !== 235)
        throw new Exception("Contraseña rechazada o Error Auth: $pass_resp");

    // Si llegamos hasta aquí, la autenticación fue exitosa
    fclose($socket);
    echo json_encode(['status' => 'success', 'msg' => '¡Conexión SMTP Exitosa! Autenticación correcta.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>