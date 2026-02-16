<?php
/**
 * mailer_utils.php
 * Utilidades para el envío de correos.
 */

function enviar_notificacion($destinatario, $asunto, $body_html)
{
    if (empty($destinatario))
        return false;

    // Cargar config
    $config = require __DIR__ . '/config_mail.php';

    // Headers básicos para HTML
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $config['from_name'] . ' <' . $config['from_address'] . '>' . "\r\n";

    // Si se usara PHPMailer aquí iría la lógica robusta.
    // Por ahora usamos mail() nativo de PHP con soporte básico.
    // XAMPP necesita configurar sendmail.ini si se usa localmente.

    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
            .header { background: #2563EB; color: white; padding: 15px; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; }
            .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
            .btn { display: inline-block; padding: 10px 20px; background: #2563EB; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2 style='margin:0;'>$asunto</h2>
            </div>
            <div class='content'>
                $body_html
            </div>
            <div class='footer'>
                Enviado automáticamente por el Sistema de Tickets
            </div>
        </div>
    </body>
    </html>
    ";

    try {
        if ($config['driver'] === 'smtp' && false) {
            // Placeholder para futura implementación PHPMailer
            // require 'vendor/autoload.php'; ...
        } else {
            // Fallback native mail
            return mail($destinatario, $asunto, $mensaje, $headers);
        }
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $e->getMessage());
        return false;
    }
}
?>