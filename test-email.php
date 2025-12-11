<?php
/**
 * Script de prueba de correo - PHP Puro
 * Prueba la configuración SMTP para enviar correos
 */

// Configuración del servidor SMTP
$smtpHost = 'Ferromex-mx.mail.protection.outlook.com';
$smtpPort = 25;
$fromEmail = 'laviaquenosune@ferromex.mx';
$fromName = 'Eventos';
$toEmail = 'desarrollo@peltiermkt.com';

// Construir el mensaje
$subject = 'Prueba de correo - ' . date('Y-m-d H:i:s');
$message = "Este es un correo de prueba enviado desde PHP.\n\n";
$message .= "Servidor: {$smtpHost}:{$smtpPort}\n";
$message .= "Desde: {$fromEmail}\n";
$message .= "Fecha: " . date('Y-m-d H:i:s') . "\n";

// Headers del correo
$headers = [
    "From: {$fromName} <{$fromEmail}>",
    "Reply-To: {$fromEmail}",
    "X-Mailer: PHP/" . phpversion(),
    "MIME-Version: 1.0",
    "Content-Type: text/plain; charset=UTF-8"
];

echo "═══════════════════════════════════════════════════════════\n";
echo "  PRUEBA DE ENVÍO DE CORREO - PHP\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Configuración:\n";
echo "  SMTP Host: {$smtpHost}\n";
echo "  SMTP Port: {$smtpPort}\n";
echo "  From: {$fromEmail}\n";
echo "  To: {$toEmail}\n";
echo "  Subject: {$subject}\n\n";

echo "Intentando enviar correo...\n";

// Configurar el servidor SMTP en php.ini (temporal)
ini_set('SMTP', $smtpHost);
ini_set('smtp_port', $smtpPort);
ini_set('sendmail_from', $fromEmail);

// Intentar enviar el correo
$result = mail($toEmail, $subject, $message, implode("\r\n", $headers));

if ($result) {
    echo "\n✓ ¡Correo enviado exitosamente!\n";
    echo "  Verifica la bandeja de entrada de {$toEmail}\n";
} else {
    echo "\n✗ Error al enviar el correo\n";
    echo "  Revisa la configuración del servidor SMTP\n";
    
    $lastError = error_get_last();
    if ($lastError) {
        echo "  Error: {$lastError['message']}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
