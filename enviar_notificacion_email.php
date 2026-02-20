<?php
/**
 * enviar_notificacion_email.php
 * Función helper reutilizable para enviar alertas por correo.
 * Usa la configuración SMTP de config_mail.php.
 */

if (!function_exists('enviarAlertaEmail')) {

    function enviarAlertaEmail(string $to, string $subject, string $htmlBody): bool
    {
        if (empty(trim($to)))
            return false;

        $config = [];
        $config_path = __DIR__ . '/config_mail.php';
        if (file_exists($config_path)) {
            $config = require $config_path;
        }

        $from_address = $config['from_address'] ?? ($config['username'] ?? 'noreply@sistema.local');
        $from_name = $config['from_name'] ?? 'Sistema de Tickets';
        $host = $config['host'] ?? 'localhost';
        $port = (int) ($config['port'] ?? 587);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $encryption = $config['encryption'] ?? 'tls';

        // --- Intentar con PHPMailer si existe ---
        $phpmailer_paths = [
            __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
            __DIR__ . '/PHPMailer/PHPMailer.php',
        ];
        foreach ($phpmailer_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                break;
            }
        }

        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mailer->isSMTP();
                $mailer->Host = $host;
                $mailer->SMTPAuth = !empty($username);
                $mailer->Username = $username;
                $mailer->Password = $password;
                $mailer->SMTPSecure = $encryption === 'ssl'
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mailer->Port = $port;
                $mailer->setFrom($from_address, $from_name);
                $mailer->addAddress($to);
                $mailer->isHTML(true);
                $mailer->CharSet = 'UTF-8';
                $mailer->Subject = $subject;
                $mailer->Body = $htmlBody;
                $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
                return $mailer->send();
            } catch (\Exception $e) {
                error_log("enviarAlertaEmail (PHPMailer): " . $e->getMessage());
                return false;
            }
        }

        // --- Fallback: mail() nativo de PHP ---
        $boundary = md5(uniqid());
        $headers = implode("\r\n", [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_address}>",
            "Reply-To: {$from_address}",
            "X-Mailer: Sistema-Tickets",
        ]);

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
        if (!$sent) {
            error_log("enviarAlertaEmail (mail): Failed to send to {$to}");
        }
        return (bool) $sent;
    }
}

/**
 * Genera el cuerpo HTML de la alerta.
 */
if (!function_exists('emailAlertaHTML')) {
    function emailAlertaHTML(string $titulo, string $cuerpo, string $enlaceTexto = '', string $enlaceUrl = ''): string
    {
        $btn = '';
        if ($enlaceUrl && $enlaceTexto) {
            $btn = "<a href=\"{$enlaceUrl}\" style=\"display:inline-block;margin-top:20px;padding:12px 28px;background:#4f46e5;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:15px;\">{$enlaceTexto}</a>";
        }
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:28px 32px;">
          <h1 style="margin:0;color:#fff;font-size:20px;font-weight:700;">🎫 Sistema de Tickets</h1>
          <p style="margin:4px 0 0;color:rgba(255,255,255,.8);font-size:13px;">Notificación automática</p>
        </td></tr>
        <!-- Body -->
        <tr><td style="padding:32px;">
          <h2 style="margin:0 0 12px;color:#1e293b;font-size:18px;">{$titulo}</h2>
          <div style="color:#475569;font-size:15px;line-height:1.7;">{$cuerpo}</div>
          {$btn}
        </td></tr>
        <!-- Footer -->
        <tr><td style="background:#f8fafc;padding:16px 32px;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#94a3b8;font-size:12px;">Este es un mensaje automático del Sistema de Tickets. Por favor no responda este correo.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
