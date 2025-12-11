<?php
/**
 * Script de prueba de correo SMTP - Prueba con y sin autenticación
 * Este script prueba ambas configuraciones automáticamente
 */

$smtpHost = 'Ferromex-mx.mail.protection.outlook.com';
$smtpPort = 25;
$fromEmail = 'laviaquenosune@ferromex.mx';
$fromName = 'Eventos';
$toEmail = 'desarrollo@peltiermkt.com';
$smtpUsername = 'laviaquenosune@ferromex.mx';
$smtpPassword = '1Fxe#Gmxt';

function testEmailWithAuth($config, $useAuth = false) {
    echo "\n═══════════════════════════════════════════════════════════\n";
    echo "  PRUEBA " . ($useAuth ? "CON" : "SIN") . " AUTENTICACIÓN\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    // Conectar
    echo "Conectando a {$config['host']}:{$config['port']}...\n";
    $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);
    
    if (!$socket) {
        echo "✗ Error de conexión: {$errstr} ({$errno})\n";
        return false;
    }
    
    echo "✓ Conexión establecida\n\n";
    
    // Leer banner
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    // EHLO
    fwrite($socket, "EHLO localhost\r\n");
    echo "> EHLO localhost\n";
    
    $extensions = [];
    while ($line = fgets($socket, 515)) {
        echo "< {$line}";
        if (preg_match('/^250[ -](.+)$/i', trim($line), $matches)) {
            $extensions[] = strtoupper(trim($matches[1]));
        }
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // Verificar soporte AUTH
    $supportsAuth = false;
    foreach ($extensions as $ext) {
        if (strpos($ext, 'AUTH') === 0) {
            $supportsAuth = true;
            echo "\n✓ Servidor soporta autenticación\n";
            break;
        }
    }
    
    if (!$supportsAuth) {
        echo "\n⚠ Servidor NO soporta autenticación\n";
    }
    
    // Intentar autenticación si se solicita
    if ($useAuth && $supportsAuth) {
        echo "\nIntentando autenticación LOGIN...\n";
        fwrite($socket, "AUTH LOGIN\r\n");
        echo "> AUTH LOGIN\n";
        
        $response = fgets($socket, 515);
        echo "< {$response}";
        
        if (substr($response, 0, 3) == '334') {
            // Enviar usuario
            $userB64 = base64_encode($config['username']);
            fwrite($socket, $userB64 . "\r\n");
            echo "> " . str_repeat('*', 20) . "\n";
            
            $response = fgets($socket, 515);
            echo "< {$response}";
            
            if (substr($response, 0, 3) == '334') {
                // Enviar password
                $passB64 = base64_encode($config['password']);
                fwrite($socket, $passB64 . "\r\n");
                echo "> " . str_repeat('*', 20) . "\n";
                
                $response = fgets($socket, 515);
                echo "< {$response}";
                
                if (substr($response, 0, 3) == '235') {
                    echo "✓ Autenticación exitosa!\n\n";
                } else {
                    echo "✗ Error de autenticación\n\n";
                    fclose($socket);
                    return false;
                }
            }
        }
    } else {
        echo "\n";
    }
    
    // MAIL FROM
    echo "Enviando comandos de correo...\n";
    fwrite($socket, "MAIL FROM:<{$config['from']}>\r\n");
    echo "> MAIL FROM:<{$config['from']}>\n";
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    if (substr($response, 0, 3) != '250') {
        echo "✗ Error en MAIL FROM\n";
        fclose($socket);
        return false;
    }
    
    // RCPT TO
    fwrite($socket, "RCPT TO:<{$config['to']}>\r\n");
    echo "> RCPT TO:<{$config['to']}>\n";
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    if (substr($response, 0, 3) != '250') {
        echo "✗ Error en RCPT TO\n";
        fclose($socket);
        return false;
    }
    
    // DATA
    fwrite($socket, "DATA\r\n");
    echo "> DATA\n";
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    if (substr($response, 0, 3) != '354') {
        echo "✗ Error en DATA\n";
        fclose($socket);
        return false;
    }
    
    // Construir mensaje
    $authMethod = $useAuth ? "con autenticación" : "sin autenticación";
    $subject = "Prueba SMTP {$authMethod} - " . date('Y-m-d H:i:s');
    
    $message = "From: {$config['name']} <{$config['from']}>\r\n";
    $message .= "To: <{$config['to']}>\r\n";
    $message .= "Subject: {$subject}\r\n";
    $message .= "Date: " . date('r') . "\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "\r\n";
    $message .= "Este es un correo de prueba enviado {$authMethod}.\n\n";
    $message .= "Detalles:\n";
    $message .= "- Servidor: {$config['host']}:{$config['port']}\n";
    $message .= "- Autenticación: " . ($useAuth ? "SÍ" : "NO") . "\n";
    $message .= "- Usuario: " . ($useAuth ? $config['username'] : "N/A") . "\n";
    $message .= "- Desde: {$config['from']}\n";
    $message .= "- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $message .= "\r\n.\r\n";
    
    echo "> [Enviando contenido del mensaje...]\n";
    fwrite($socket, $message);
    
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    $success = substr($response, 0, 3) == '250';
    
    // QUIT
    fwrite($socket, "QUIT\r\n");
    echo "> QUIT\n";
    $response = fgets($socket, 515);
    echo "< {$response}";
    
    fclose($socket);
    
    if ($success) {
        echo "\n✓ ¡Correo enviado exitosamente!\n";
    } else {
        echo "\n✗ Error al enviar el correo\n";
    }
    
    return $success;
}

// Configuración
$config = [
    'host' => $smtpHost,
    'port' => $smtpPort,
    'from' => $fromEmail,
    'to' => $toEmail,
    'name' => $fromName,
    'username' => $smtpUsername,
    'password' => $smtpPassword,
];

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  PRUEBA COMPLETA DE SMTP - CON Y SIN AUTENTICACIÓN       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";

// Prueba 1: Sin autenticación
$result1 = testEmailWithAuth($config, false);

sleep(2); // Esperar 2 segundos entre pruebas

// Prueba 2: Con autenticación
$result2 = testEmailWithAuth($config, true);

// Resumen
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE PRUEBAS\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Sin autenticación: " . ($result1 ? "✓ ÉXITO" : "✗ FALLÓ") . "\n";
echo "Con autenticación: " . ($result2 ? "✓ ÉXITO" : "✗ FALLÓ") . "\n\n";

if ($result1 || $result2) {
    echo "Verifica la bandeja de entrada de {$toEmail}\n";
    echo "Deberías recibir " . ($result1 && $result2 ? "2 correos" : "1 correo") . "\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
